<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class IdAnalyzerService
{
    private function maskKey(string $apiKey): string
    {
        $len = strlen($apiKey);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . substr($apiKey, -4);
    }

    private function getBaseUrl(): string
    {
        return rtrim((string) config('services.id_analyzer.api_url', 'https://api2.idanalyzer.com'), '/');
    }

    private function getSessionEndpoint(): string
    {
        return trim((string) config('services.id_analyzer.session_endpoint', 'docupass'), '/');
    }

    private function buildUrl(string $path): string
    {
        $path = ltrim($path, '/');
        return $this->getBaseUrl() . '/' . $path;
    }

    private function extractSessionData(array $sessionData): array
    {
        // For Docupass, the reference is the primary identifier
        $sessionId = $sessionData['reference']
            ?? $sessionData['session_id']
            ?? $sessionData['verification']['id']
            ?? $sessionData['id']
            ?? null;

        $verificationUrl = $sessionData['url']
            ?? $sessionData['verification']['url']
            ?? $sessionData['docupass_url']
            ?? $sessionData['link']
            ?? null;

        \Log::info('ID Analyzer session data extracted', [
            'session_id' => $sessionId,
            'has_url' => !empty($verificationUrl),
            'response_keys' => array_keys($sessionData)
        ]);

        return [$sessionId, $verificationUrl];
    }

    public function createSession(array $payload = [])
    {
        $apiKey = trim((string) config('services.id_analyzer.api_key'));
        $sessionEndpoint = $this->getSessionEndpoint();
        $url = $this->buildUrl($sessionEndpoint);

        
        \Log::info('ID Analyzer createSession request', [
            'api_url' => $this->getBaseUrl(),
            'endpoint' => $sessionEndpoint,
            'profile_id' => $payload['profile_id'] ?? $payload['profile'] ?? null,
            'profile' => $payload['profile'] ?? $payload['profile_id'] ?? null,
            'api_key_tail' => $this->maskKey($apiKey),
            'api_key_len' => strlen($apiKey),
            'api_key_has_space' => preg_match('/\s/', $apiKey) === 1,
            'x_api_key_header_len' => strlen($apiKey),
            'payload_hash' => $payload ? hash('sha256', json_encode($payload)) : null,
        ]);

        try {
            $profileValue = $payload['profile_id'] ?? $payload['profile'] ?? null;
            if (empty($profileValue)) {
                $profileValue = (string) config('services.id_analyzer.profile_id');
            }

            if (!empty($profileValue)) {
                $payload['profile'] = $profileValue;
                $payload['profile_id'] = $profileValue;
            }

            $headers = [
                'Authorization' => $apiKey,
                'x-api-key' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            $jsonPayload = !empty($payload) ? $payload : (object) [];

            try {
                $response = Http::withHeaders($headers)
                    ->timeout(30)
                    ->post($url, $jsonPayload);

                if ($response->status() === 404 && $sessionEndpoint !== 'sessions') {
                    $fallbackUrl = $this->buildUrl('sessions');
                    $response = Http::withHeaders($headers)
                        ->timeout(30)
                        ->post($fallbackUrl, $jsonPayload);
                }

                if (!$response->successful()) {
                    \Log::error('ID Analyzer createSession client error', [
                        'status' => $response->status(),
                        'url' => $url,
                        'endpoint' => $sessionEndpoint,
                        'response' => $response->body(),
                    ]);
                    throw new \RuntimeException('ID Analyzer API error: HTTP ' . $response->status());
                }
            } catch (\Exception $e) {
                throw $e;
            }

            $sessionData = $response->json();

            [$sessionId, $verificationUrl] = $this->extractSessionData(is_array($sessionData) ? $sessionData : []);

            return [
                'session_id' => $sessionId,
                'verification_url' => $verificationUrl,
                'raw' => $sessionData,
            ];
        } catch (\Exception $e) {
            $logContext = [
                'error' => $e->getMessage(),
            ];

            if ($e instanceof \GuzzleHttp\Exception\ClientException && $e->getResponse()) {
                $logContext['status'] = $e->getResponse()->getStatusCode();
                $logContext['response'] = (string) $e->getResponse()->getBody();
            }

            \Log::error('ID Analyzer createSession failed', $logContext);

            return [
                'session_id' => null,
                'verification_url' => null,
                'error' => 'ID Analyzer API error: ' . $e->getMessage(),
            ];
        }
    }

    public function getSessionStatus(string $sessionId)
    {
        $client = new Client();
        $sessionEndpoint = $this->getSessionEndpoint();
        $url = $this->buildUrl("{$sessionEndpoint}/{$sessionId}");
        $apiKey = (string) config('services.id_analyzer.api_key');

        try {
            try {
                $response = $client->get($url, [
                    'headers' => [
                        'Authorization' => $apiKey,
                        'x-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                ]);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
                if ($statusCode === 404 && $sessionEndpoint !== 'sessions') {
                    $fallbackUrl = $this->buildUrl("sessions/{$sessionId}");
                    $response = $client->get($fallbackUrl, [
                        'headers' => [
                            'Authorization' => $apiKey,
                            'x-api-key' => $apiKey,
                            'Content-Type' => 'application/json',
                        ],
                    ]);
                } else {
                    throw $e;
                }
            }

            $sessionStatus = json_decode($response->getBody(), true);

            return response()->json($sessionStatus);
        } catch (\Exception $e) {
            return response()->json(['error' => 'ID Analyzer API error: ' . $e->getMessage()], 500);
        }
    }

    public function getPersonData(string $sessionId)
    {
        $client = new Client();
        $sessionEndpoint = $this->getSessionEndpoint();
        $url = $this->buildUrl("{$sessionEndpoint}/{$sessionId}");
        $apiKey = (string) config('services.id_analyzer.api_key');
        $secretKey = (string) config('services.id_analyzer.secret_key');
        $hmacSignature = $secretKey ? hash_hmac('sha256', $sessionId, $secretKey) : null;

        $headers = [
            'Authorization' => $apiKey,
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($hmacSignature) {
            $headers['X-HMAC-SIGNATURE'] = $hmacSignature;
        }

        try {
            try {
                $response = $client->get($url, [
                    'headers' => $headers,
                ]);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
                if ($statusCode === 404 && $sessionEndpoint !== 'sessions') {
                    $fallbackUrl = $this->buildUrl("sessions/{$sessionId}/decision");
                    $response = $client->get($fallbackUrl, [
                        'headers' => $headers,
                    ]);
                } else {
                    throw $e;
                }
            }

            $sessionStatus = json_decode($response->getBody(), true);

            return response()->json($sessionStatus['verification'] ?? $sessionStatus);
        } catch (\Exception $e) {
            return response()->json(['error' => 'ID Analyzer API error: ' . $e->getMessage()], 500);
        }
    }

    public function getDecision(string $sessionId)
    {
        $client = new Client();
        $apiKey = (string) config('services.id_analyzer.api_key');
        $secretKey = (string) config('services.id_analyzer.secret_key');
        $hmacSignature = $secretKey ? hash_hmac('sha256', $sessionId, $secretKey) : null;
        $sessionEndpoint = $this->getSessionEndpoint();

        try {
            $decisionUrl = $this->buildUrl("{$sessionEndpoint}/{$sessionId}");

            $headers = [
                'Authorization' => $apiKey,
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ];

            if ($hmacSignature) {
                $headers['X-HMAC-SIGNATURE'] = $hmacSignature;
            }

            try {
                $response = $client->get($decisionUrl, [
                    'headers' => $headers,
                ]);

                $sessionStatus = json_decode($response->getBody(), true);
                
                // Extract transactionId if available and fetch full transaction data
                $transactionId = $sessionStatus['transactionId'] ?? null;
                if ($transactionId) {
                    try {
                        $transactionData = $this->getTransactionData($transactionId);
                        $transactionJson = json_decode($transactionData->getContent(), true);
                        if (!empty($transactionJson)) {
                            // Merge transaction data into session status (transaction has outputImage)
                            $sessionStatus['finalTransaction'] = $transactionJson;
                        }
                    } catch (\Exception $txError) {
                        \Log::warning('Failed to fetch transaction data', [
                            'transaction_id' => $transactionId,
                            'error' => $txError->getMessage()
                        ]);
                    }
                }
                
                if (is_array($sessionStatus) && empty($sessionStatus['verification'])) {
                    $decision = $sessionStatus['decision']
                        ?? ($sessionStatus['finalTransaction']['decision'] ?? null);
                    if ($decision) {
                        $normalized = match (strtolower($decision)) {
                            'accept', 'approved', 'success' => 'approved',
                            'reject', 'rejected', 'declined', 'refused' => 'declined',
                            default => 'pending',
                        };
                        $sessionStatus['verification'] = [
                            'status' => $normalized,
                        ];
                    }
                }
                return response()->json($sessionStatus);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
                    try {
                        $sessionUrl = $this->buildUrl("sessions/{$sessionId}");
                        $sessionResponse = $client->get($sessionUrl, [
                            'headers' => [
                                'Authorization' => $apiKey,
                                'x-api-key' => $apiKey,
                                'Content-Type' => 'application/json',
                            ],
                        ]);

                        $sessionData = json_decode($sessionResponse->getBody(), true);

                        return response()->json([
                            'status' => 'success',
                            'verification' => $sessionData['verification'] ?? null,
                        ]);
                    } catch (\Exception $sessionError) {
                        return response()->json([
                            'status' => 'success',
                            'verification' => null,
                        ]);
                    }
                }

                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'success',
                'verification' => null,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Get Transaction data by transaction ID (contains outputImage with actual image IDs)
     */
    public function getTransactionData(string $transactionId)
    {
        $client = new Client();
        $apiKey = (string) config('services.id_analyzer.api_key');
        $secretKey = (string) config('services.id_analyzer.secret_key');
        $hmacSignature = $secretKey ? hash_hmac('sha256', $transactionId, $secretKey) : null;
        
        $transactionUrl = $this->buildUrl("transaction/{$transactionId}");
        
        $headers = [
            'Authorization' => $apiKey,
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ];
        
        if ($hmacSignature) {
            $headers['X-HMAC-SIGNATURE'] = $hmacSignature;
        }
        
        try {
            $response = $client->get($transactionUrl, [
                'headers' => $headers,
            ]);
            
            $transactionData = json_decode($response->getBody(), true);
            return response()->json($transactionData);
        } catch (\Exception $e) {
            \Log::error('ID Analyzer getTransactionData error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'ID Analyzer API error: ' . $e->getMessage()], 500);
        }
    }

    public function getPersonMedia(string $sessionId)
    {
        $client = new Client();
        $sessionEndpoint = $this->getSessionEndpoint();
        $url = $this->buildUrl("{$sessionEndpoint}/{$sessionId}/media");
        $apiKey = (string) config('services.id_analyzer.api_key');
        $secretKey = (string) config('services.id_analyzer.secret_key');
        $hmacSignature = $secretKey ? hash_hmac('sha256', $sessionId, $secretKey) : null;

        $headers = [
            'Authorization' => $apiKey,
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($hmacSignature) {
            $headers['X-HMAC-SIGNATURE'] = $hmacSignature;
        }

        try {
            try {
                $response = $client->get($url, [
                    'headers' => $headers,
                ]);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
                if ($statusCode === 404 && $sessionEndpoint !== 'sessions') {
                    $fallbackUrl = $this->buildUrl("sessions/{$sessionId}/media");
                    $response = $client->get($fallbackUrl, [
                        'headers' => $headers,
                    ]);
                } else {
                    throw $e;
                }
            }

            $sessionStatus = json_decode($response->getBody(), true);
            return response()->json($sessionStatus);
        } catch (\Exception $e) {
            return response()->json(['error' => 'ID Analyzer API error: ' . $e->getMessage()], 500);
        }
    }

    public function getMediaContent(string $mediaId)
    {
        $client = new Client();
        $sessionEndpoint = $this->getSessionEndpoint();
        $baseUrl = $this->getBaseUrl();
        $fallbackBaseUrl = str_contains($baseUrl, 'api2.idanalyzer.com')
            ? 'https://api.idanalyzer.com'
            : null;
        $docupassCode = request()->query('docupass');
        
        \Log::info('ID Analyzer getMediaContent request', [
            'media_id' => $mediaId,
            'docupass_code' => $docupassCode,
            'base_url' => $baseUrl,
            'endpoint' => $sessionEndpoint
        ]);

        // Priority order: imagevault is the working endpoint for ID Analyzer
        $candidateUrls = [
            $this->buildUrl("imagevault/{$mediaId}"),  // CONFIRMED WORKING
        ];
        
        // Add docupass-specific URLs if we have a reference code  
        if (!empty($docupassCode)) {
            array_unshift($candidateUrls,
                $this->buildUrl("imagevault/{$docupassCode}/{$mediaId}")
            );
        }
        
        // Add other fallback patterns
        $candidateUrls = array_merge($candidateUrls, [
            $this->buildUrl("vault/{$mediaId}"),
            $this->buildUrl("media/{$mediaId}"),
            $this->buildUrl("transaction/image/{$mediaId}"),
            $this->buildUrl("{$sessionEndpoint}/media/{$mediaId}"),
        ]);

        if (!empty($docupassCode)) {
            $candidateUrls[] = $this->buildUrl("{$sessionEndpoint}/{$docupassCode}/media/{$mediaId}");
            $candidateUrls[] = $this->buildUrl("transaction/{$docupassCode}/image/{$mediaId}");
        }

        // Try fallback base URL (api.idanalyzer.com vs api2.idanalyzer.com)
        if ($fallbackBaseUrl) {
            // Prioritize imagevault for fallback too
            $candidateUrls[] = rtrim($fallbackBaseUrl, '/') . "/imagevault/{$mediaId}";
            if (!empty($docupassCode)) {
                $candidateUrls[] = rtrim($fallbackBaseUrl, '/') . "/imagevault/{$docupassCode}/{$mediaId}";
            }
            $candidateUrls[] = rtrim($fallbackBaseUrl, '/') . "/vault/{$mediaId}";
            $candidateUrls[] = rtrim($fallbackBaseUrl, '/') . "/media/{$mediaId}";
            $candidateUrls[] = rtrim($fallbackBaseUrl, '/') . "/transaction/image/{$mediaId}";
            $candidateUrls[] = rtrim($fallbackBaseUrl, '/') . "/{$sessionEndpoint}/media/{$mediaId}";
            if (!empty($docupassCode)) {
                $candidateUrls[] = rtrim($fallbackBaseUrl, '/') . "/{$sessionEndpoint}/{$docupassCode}/media/{$mediaId}";
            }
        }
        $apiKey = (string) config('services.id_analyzer.api_key');
        $secretKey = (string) config('services.id_analyzer.secret_key');
        $hmacSignature = $secretKey ? hash_hmac('sha256', $mediaId, $secretKey) : null;

        $headers = [
            'X-API-KEY' => $apiKey,
            'x-api-key' => $apiKey,
            'Authorization' => $apiKey,
            'Accept' => 'image/jpeg,image/png,image/*,*/*',
        ];

        if ($hmacSignature) {
            $headers['X-HMAC-SIGNATURE'] = $hmacSignature;
        }
        
        \Log::info('ID Analyzer media request headers', [
            'api_key_length' => strlen($apiKey),
            'has_hmac' => !empty($hmacSignature)
        ]);

        try {
            $lastError = null;
            $attemptCount = 0;

            foreach ($candidateUrls as $url) {
                $attemptCount++;
                \Log::info("ID Analyzer media attempt {$attemptCount}", ['url' => $url]);
                
                try {
                    $response = $client->get($url, [
                        'headers' => $headers,
                    ]);
                    $stream = $response->getBody();
                    if (method_exists($stream, 'rewind')) {
                        $stream->rewind();
                    }
                    $binary = $stream->getContents();
                    if ($binary === '' && is_string($stream)) {
                        $binary = $stream;
                    }
                    if ($binary !== '') {
                        if (str_starts_with($binary, "\r\n\xFF\xD8")) {
                            $binary = substr($binary, 2);
                        } elseif (str_starts_with($binary, "\n\xFF\xD8")) {
                            $binary = substr($binary, 1);
                        }
                    }

                    \Log::info('ID Analyzer media SUCCESS', [
                        'url' => $url,
                        'attempt' => $attemptCount,
                        'size' => strlen($binary)
                    ]);

                    return response($binary, 200)
                        ->header('Content-Type', $response->getHeader('Content-Type')[0]);
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
                    \Log::warning("ID Analyzer media attempt {$attemptCount} failed", [
                        'url' => $url,
                        'status' => $statusCode
                    ]);
                    
                    if ($statusCode === 404) {
                        $lastError = $e;
                        continue;
                    }

                    throw $e;
                }
            }

            $message = $lastError ? $lastError->getMessage() : 'Media not found';
            \Log::error('ID Analyzer media ALL attempts failed', [
                'media_id' => $mediaId,
                'attempts' => $attemptCount,
                'last_error' => $message
            ]);
            
            return response()->json(['error' => 'ID Analyzer API error: ' . $message], 404);
        } catch (\Exception $e) {
            \Log::error('ID Analyzer media exception', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'ID Analyzer API error: ' . $e->getMessage()], 500);
        }
    }
}
