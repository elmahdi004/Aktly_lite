<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Models\Demande;
use Illuminate\Support\Str;

class OpenAIController extends Controller
{
    private $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function getChatCompletion($demande_id)
    {
        if (empty(config('services.gemini.api_key')) && empty(config('services.openai.api_key'))) {
            return response()->json(['error' => 'LLM API key is not configured'], 503);
        }

        $demande = Demande::find($demande_id);
        if (!$demande) {
            return response()->json(['error' => 'Demande not found'], 404);
        }

        $missingFields = $this->openAIService->getMissingFields($demande);
        if (!empty($missingFields)) {
            return response()->json(['missing_fields' => $missingFields], 422);
        }

        if ($demande->pub_text) {
            $cleaned = $this->openAIService->sanitizePvText($demande->pub_text);
            if ($cleaned !== $demande->pub_text) {
                $demande->pub_text = $cleaned;
                $demande->save();
            }
            return response()->json(['response' => $cleaned], 200);
        }

        try {
            $message = $this->openAIService->buildMessage($demande);
            $response = $this->openAIService->getChatCompletionWithAssistant($message);
        } catch (\Throwable $e) {
            \Log::error('OpenAI error: ' . $e->getMessage());
            return response()->json(['error' => 'OpenAI request failed'], 500);
        }
        \Log::info('OpenAI response: ' . $response);
        if ($response != null) {
            $cleaned = $this->openAIService->sanitizePvText($response);
            $demande->pub_text = $cleaned;
            $demande->save();
        
            return response()->json(['response' => $cleaned], 200);
        }

        return response()->json(['error' => 'Failed to get response from OpenAI'], 500);
    }

    public function getExtraitTextWithIA($demande_id)
    {
        if (empty(config('services.gemini.api_key')) && empty(config('services.openai.api_key'))) {
            return response()->json(['error' => 'LLM API key is not configured'], 503);
        }

        $demande = Demande::find($demande_id);
        if (!$demande) {
            return response()->json(['error' => 'Demande not found'], 404);
        }

        $missingFields = $this->openAIService->getMissingFields($demande);
        if (!empty($missingFields)) {
            return response()->json(['missing_fields' => $missingFields], 422);
        }

        if ($demande->extrait_pub_text) {
            $cleaned = $this->openAIService->sanitizeDocumentText($demande->extrait_pub_text);
            if ($cleaned !== $demande->extrait_pub_text) {
                $demande->extrait_pub_text = $cleaned;
                $demande->save();
            }
            return response()->json(['response' => $cleaned], 200);
        }

        if (!$demande->pub_text) {
            $message = $this->openAIService->buildMessage($demande);
            $pvResponse = $this->openAIService->getChatCompletionWithAssistant($message);
            if ($pvResponse != null) {
                $cleanedPv = $this->openAIService->sanitizePvText($pvResponse);
                $demande->pub_text = $cleanedPv;
                $demande->save();
            } else {
                return response()->json(['error' => 'PV not ready yet'], 503);
            }
        }

        $message = 'generer un extrait de ce pv " : ' . $demande->pub_text . ' " en langue '. $demande->langue_entreprise;

        try {
            $response = $this->openAIService->getChatCompletion($message);
        } catch (\Throwable $e) {
            \Log::error('OpenAI extrait error: ' . $e->getMessage());
            return response()->json(['error' => 'OpenAI request failed'], 500);
        }
        \Log::info('OpenAI extrait response: ' . $response);
        if ($response != null) {
            $cleaned = $this->openAIService->sanitizeDocumentText($response);
            $demande->extrait_pub_text = $cleaned;
            $demande->save();
        
            return response()->json(['response' => $cleaned], 200);
        }

        return response()->json(['error' => 'Failed to get response from OpenAI'], 503);
    }

    public function improveText(Request $request)
    {
        if (empty(config('services.gemini.api_key')) && empty(config('services.openai.api_key'))) {
            return response()->json(['error' => 'LLM API key is not configured'], 503);
        }

        $text = trim((string) $request->input('text', ''));
        $language = $request->input('language', 'fr');
        $language = $language === 'nl' ? 'nl' : 'fr';
        $instruction = trim((string) $request->input('instruction', ''));

        if ($text === '' || Str::length($text) < 30) {
            return response()->json(['error' => 'Text is too short to improve'], 422);
        }

        try {
            $response = $this->openAIService->improveDocumentText($text, $language, $instruction);
        } catch (\Throwable $e) {
            \Log::error('OpenAI improve error: ' . $e->getMessage());
            return response()->json(['error' => 'OpenAI request failed'], 500);
        }

        if ($response != null) {
            $cleaned = $this->openAIService->sanitizeDocumentText($response);
            return response()->json(['response' => $cleaned], 200);
        }

        return response()->json(['error' => 'Failed to get response from OpenAI'], 503);
    }
}