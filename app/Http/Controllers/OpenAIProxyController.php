<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OpenAIProxyController extends Controller
{
    // Proxy GET request to the external OpenAI conversion service
    public function convert($enterprise_number)
    {
        // Allow longer processing time for heavy PDF conversions
        set_time_limit(0); // Unlimited PHP execution time
        ini_set('max_execution_time', 0);
        
        try {
            $baseUrls = config('services.openai_convert.urls', []);
            if (!is_array($baseUrls) || empty($baseUrls)) {
                $baseUrls = ["https://openai-legate.onrender.com"];
            }

            $lastResponse = null;
            foreach ($baseUrls as $baseUrl) {
                $baseUrl = rtrim((string) $baseUrl, '/');
                if ($baseUrl === '') {
                    continue;
                }
                $externalUrl = $baseUrl . "/convert-pdf/" . $enterprise_number;
                \Log::info('OpenAI proxy request', [
                    'enterprise' => $enterprise_number,
                    'url' => $externalUrl,
                ]);

                $response = Http::withoutVerifying()
                    ->timeout(300)
                    ->connectTimeout(30)
                    ->retry(0, 0, null, false)
                    ->get($externalUrl);

                \Log::info('OpenAI proxy response', [
                    'enterprise' => $enterprise_number,
                    'status' => $response->status(),
                    'content_type' => $response->header('Content-Type'),
                    'url' => $externalUrl,
                ]);

                $lastResponse = $response;
                if ($response->successful()) {
                    return response($response->body(), $response->status())
                        ->header('Content-Type', $response->header('Content-Type'));
                }
            }

            if ($lastResponse) {
                return response($lastResponse->body(), $lastResponse->status())
                    ->header('Content-Type', $lastResponse->header('Content-Type'));
            }

            return response()->json([
                'error' => 'No conversion endpoint available',
            ], 503);
        } catch (\Throwable $e) {
            \Log::error('OpenAI proxy error', [
                'error' => $e->getMessage(),
                'enterprise' => $enterprise_number,
                'url' => $externalUrl ?? null,
            ]);
            return response()->json([
                'error' => 'Proxy request failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
