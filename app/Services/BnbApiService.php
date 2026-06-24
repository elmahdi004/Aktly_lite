<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class BnbApiService
{
    protected ?string $baseUrl = null;
    protected ?string $apiKey = null;

    public function __construct()
    {
        $this->baseUrl = config('services.bnb.base_url') ?: config('services.bnb.base_url_dev');
        $this->apiKey = config('services.bnb.api_key');
    }

    /**
     * Récupère la langue d’une entreprise via son numéro BCE.
     *
     * @param string $companyNumber
     * @return string|null
     */
    public function getCompanyLanguage(string $companyNumber): ?string
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            Log::warning('BNB API non configurée', [
                'base_url_present' => !empty($this->baseUrl),
                'api_key_present' => !empty($this->apiKey),
            ]);
            return null;
        }
        
        $endpoint = "{$this->baseUrl}/authentic/legalEntity/{$companyNumber}/references";
        Log::info('Appel API BNB', [
            'url' => $endpoint,
            'company_number' => $companyNumber,
        ]);

        try {
            $response = Http::withHeaders([
                'NBB-CBSO-Subscription-Key' => $this->apiKey,
                'X-Request-Id' => Str::uuid()->toString(),
                'Accept' => 'application/json',
            ])->get($endpoint);
            if ($response->successful()) {
                $data = $response->json();
                Log::info('Réponse API BNB', [
                    'status' => $response->status(),
                    'body' => $data[0]['Language'] ?? null,
                ]);
                // Supposons que la langue soit dans $data['language']
                return $data[0]['Language'] ?? null;
            }

            Log::error('Erreur API BNB', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('Exception API BNB', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
