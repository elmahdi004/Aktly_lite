<?php
namespace App\Services;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Session;

class VeriffService{

    /**
     * Create a Veriff verification session.
     * Veriff requires a signed request: X-HMAC-SIGNATURE = HMAC-SHA256(rawBody, secret),
     * and a body containing a `verification` object (vendorData/person/callback).
     *
     * @param array $opts vendorData, firstName, lastName, callback
     * @return array{session_id?:string, verification_url?:string, error?:string}
     */
    public function createSession(array $opts = [])
    {
        $client = new Client();
        $base = rtrim(env('VERIFF_API_URL', 'https://stationapi.veriff.com/v1'), '/');
        $url = $base . '/sessions';
        $apiKey = (string) env('VERIFF_API_KEY');
        $secretKey = (string) env('VERIFF_SECRET_KEY');

        if ($apiKey === '' || $secretKey === '') {
            return ['error' => 'Veriff credentials are not configured.'];
        }

        $verification = ['vendorData' => (string) ($opts['vendorData'] ?? '')];
        if (!empty($opts['callback'])) {
            $verification['callback'] = $opts['callback'];
        }
        $person = array_filter([
            'firstName' => $opts['firstName'] ?? null,
            'lastName' => $opts['lastName'] ?? null,
        ]);
        if (!empty($person)) {
            $verification['person'] = $person;
        }

        $body = json_encode(['verification' => $verification], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $body, $secretKey);

        try {
            $response = $client->post($url, [
                'headers' => [
                    'X-AUTH-CLIENT' => $apiKey,
                    'X-HMAC-SIGNATURE' => $signature,
                    'Content-Type' => 'application/json',
                ],
                'body' => $body,
            ]);

            $data = json_decode($response->getBody(), true);
            $sessionId = $data['verification']['id'] ?? null;
            $verificationUrl = $data['verification']['url'] ?? null;

            if (!$sessionId || !$verificationUrl) {
                \Log::error('Veriff createSession: missing id/url', ['response' => $data]);
                return ['error' => 'Veriff session response is missing id/url.'];
            }

            return [
                'session_id' => $sessionId,
                'verification_url' => $verificationUrl,
            ];
        } catch (\Throwable $e) {
            $resp = method_exists($e, 'getResponse') && $e->getResponse() ? (string) $e->getResponse()->getBody() : null;
            \Log::error('Veriff createSession failed', ['error' => $e->getMessage(), 'body' => $resp]);
            return ['error' => 'Veriff API error: ' . $e->getMessage()];
        }
    }
    public function getSessionStatus($sessionId)
    {
        $client = new Client();
        $url = env('VERIFF_API_URL', 'https://stationapi.veriff.com/v1') . "/sessions/{$sessionId}";
        $apiKey = env('VERIFF_API_KEY');

        try {
            $response = $client->get($url, [
                'headers' => [
                    'X-AUTH-CLIENT' => $apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $sessionStatus = json_decode($response->getBody(), true);

            return response()->json($sessionStatus);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Veriff API error: ' . $e->getMessage()], 500);
        }
    }
    public function getPersonData($sessionId){
        $client = new Client();
        $url = env('VERIFF_API_URL', 'https://stationapi.veriff.com/v1') . "/sessions/{$sessionId}/decision";
        $apiKey = env('VERIFF_API_KEY');
        $secretKey = env('VERIFF_SECRET_KEY');
        $hmacSignature = hash_hmac('sha256', $sessionId, $secretKey);

        try {
            $response = $client->get($url, [
                'headers' => [
                    'X-AUTH-CLIENT' => $apiKey,
                    'Content-Type' => 'application/json',
                    'X-HMAC-SIGNATURE' => $hmacSignature
                ],
            ]);

            $sessionStatus = json_decode($response->getBody(), true);

            return response()->json($sessionStatus['verification']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Veriff API error: ' . $e->getMessage()], 500);
        }
    }
    public function handleCallback()
    {
        $sessionId =  Session::get('veriff_active_session');
        // $sessionId =  '9ae6257d-4e53-4920-89a9-a1c50f586dd9';
        if($sessionId){
            $decision = json_decode($this->getDecision($sessionId)->getContent());
            if($decision->verification->status === 'approved'){
                $kboData = Session::get('api-data');
                if(strtolower($kboData['function'][0]['person']['givenName']) == strtolower($decision->verification->person->firstName) && strtolower($kboData['function'][0]['person']['surname']) == strtolower($decision->verification->person->lastName)){
                    Session::put('veriff_active_person',$decision);
                    return redirect()->route('pdfs');
                }else{
                    return redirect()->route('idanalyzer.error_page')->with("error" , "We have an error please try later");
                    // return response()->json(['givenName' => strtolower($kboData['function'][0]['person']['givenName']),'firstName' => strtolower($decision->verification->person->firstName)], 500);
                }
            }
        return redirect()->route('idanalyzer.error_page')->with('error' , "Oops! It looks like we couldn't verify your ID. This might be due to a blurry photo, incorrect details, or an unsupported ID type. No worries—please double-check your document and try again. If you need help, feel free to reach out to our support team!");
        }
        return redirect()->route('idanalyzer.error_page')->with("error" , "We have an error can you try later");
    }
    public function getDecision($sessionId){
        $client = new Client();
        $apiKey = env('VERIFF_API_KEY');
        $secretKey = env('VERIFF_SECRET_KEY');
        $hmacSignature = hash_hmac('sha256', $sessionId, $secretKey);

        try {
            // First try to get decision (if verification is complete)
            $decisionUrl = env('VERIFF_API_URL', 'https://stationapi.veriff.com/v1') . "/sessions/{$sessionId}/decision";
            try {
                $response = $client->get($decisionUrl, [
                'headers' => [
                    'X-AUTH-CLIENT' => $apiKey,
                    'Content-Type' => 'application/json',
                    'X-HMAC-SIGNATURE' => $hmacSignature
                ],
            ]);

            $sessionStatus = json_decode($response->getBody(), true);
            return response()->json($sessionStatus);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                // If decision endpoint returns 404, try to get session status instead
                if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
                    try {
                        $sessionUrl = env('VERIFF_API_URL', 'https://stationapi.veriff.com/v1') . "/sessions/{$sessionId}";
                        $sessionResponse = $client->get($sessionUrl, [
                            'headers' => [
                                'X-AUTH-CLIENT' => $apiKey,
                                'Content-Type' => 'application/json',
                            ],
                        ]);
                        
                        $sessionData = json_decode($sessionResponse->getBody(), true);
                        // Return in the same format as decision endpoint
                        // If verification exists but no decision yet, return null for verification
                        return response()->json([
                            'status' => 'success',
                            'verification' => $sessionData['verification'] ?? null
                        ]);
                    } catch (\Exception $sessionError) {
                        // If session also doesn't exist, return the original error
                        return response()->json([
                            'status' => 'success',
                            'verification' => null
                        ]);
                    }
                }
                throw $e;
            }
        } catch (\Exception $e) {
            // Return a response with null verification instead of error
            // This allows the frontend to handle "pending" status
            return response()->json([
                'status' => 'success',
                'verification' => null,
                'error' => $e->getMessage()
            ]);
        }
    }
    // route for display not found page 
    public function notFoundPage(){
        return view('idanalyzer.not_found');
    }
    // route for display verification declined page 
    public function veriffDeclinedPage(){
        return view('idanalyzer.verification_declined');
    }
    public function errorPage(){
        return view('idanalyzer.error_page');
    }
    public function getPersonMedia($sessionId){
        $client = new Client();
        $url = env('VERIFF_API_URL', 'https://stationapi.veriff.com/v1') . "/sessions/{$sessionId}/media";
        $apiKey = env('VERIFF_API_KEY');
        $secretKey = env('VERIFF_SECRET_KEY');
        $hmacSignature = hash_hmac('sha256', $sessionId, $secretKey);
    
        try {
            $response = $client->get($url, [
                'headers' => [
                    'X-AUTH-CLIENT' => $apiKey,
                    'Content-Type' => 'application/json',
                    'X-HMAC-SIGNATURE' => $hmacSignature
                ],
            ]);
    
            $sessionStatus = json_decode($response->getBody(), true);
            return response()->json($sessionStatus);
            // $this->getMediaContent($sessionStatus['images'][0]['id']);
            
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Veriff API error: ' . $e->getMessage()], 500);
        }
    }


    public function getMediaContent($mediaId)
{
    $client = new Client();
    $url = env('VERIFF_API_URL', 'https://stationapi.veriff.com/v1') . "/media/{$mediaId}";
    $apiKey = env('VERIFF_API_KEY');
    $secretKey = env('VERIFF_SECRET_KEY');
    $hmacSignature = hash_hmac('sha256', $mediaId, $secretKey);

    try {
        $response = $client->get($url, [
            'headers' => [
                'X-AUTH-CLIENT' => $apiKey,
                'X-HMAC-SIGNATURE' => $hmacSignature,
            ],
        ]);

        return response($response->getBody(), 200)
            ->header('Content-Type', $response->getHeader('Content-Type')[0]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Veriff API error: ' . $e->getMessage()], 500);
    }
}
}
