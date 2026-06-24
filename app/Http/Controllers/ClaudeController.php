<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Services\OpenAIService;
use App\Services\PdfFieldsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClaudeController extends Controller
{
    private OpenAIService $openAIService;
    private PdfFieldsService $pdfFieldsService;

    public function __construct(OpenAIService $openAIService, PdfFieldsService $pdfFieldsService)
    {
        $this->openAIService = $openAIService;
        $this->pdfFieldsService = $pdfFieldsService;
    }

    public function suggestPdfFields(Request $request, Demande $demande)
    {
        if (empty(config('services.openai.api_key'))) {
            return response()->json(['error' => 'OpenAI API key is not configured'], 503);
        }

        $form = strtolower((string) $request->input('form', ''));
        $instruction = trim((string) $request->input('instruction', ''));
        $language = $request->input('language', $demande->langue_entreprise ?? 'fr');
        $language = $language === 'nl' ? 'nl' : 'fr';

        if ($instruction === '') {
            return response()->json(['error' => 'Instruction is required'], 422);
        }

        if (!in_array($form, ['formulaire1', 'formulaire2', 'all'], true)) {
            return response()->json(['error' => 'Unsupported form'], 422);
        }

        $fields = $this->pdfFieldsService->getFormFields($demande, $form);
        $requestedFields = $request->input('fields');
        if (is_array($requestedFields) && !empty($requestedFields)) {
            $allowed = array_flip(array_filter($requestedFields, 'is_string'));
            $fields = array_values(array_filter($fields, function ($field) use ($allowed) {
                $key = $field['key'] ?? '';
                return $key !== '' && isset($allowed[$key]);
            }));
        }
        if (empty($fields)) {
            return response()->json([
                'updates' => [],
                'message' => 'No editable fields for this form',
            ]);
        }

        $updates = $this->openAIService->suggestPdfFieldUpdates($fields, $instruction, $language);

        $allowedKeys = array_flip(array_map(function ($field) {
            return $field['key'] ?? '';
        }, $fields));

        $filtered = [];
        foreach ($updates as $update) {
            if (!is_array($update)) {
                continue;
            }
            $key = $update['key'] ?? '';
            $value = $update['value'] ?? null;
            if ($key === '' || !isset($allowedKeys[$key])) {
                continue;
            }
            if (is_string($value)) {
                $value = trim($value);
            }
            if ($value === null || $value === '') {
                continue;
            }
            $filtered[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        Log::info('OpenAI PDF field suggestions', [
            'demande_id' => $demande->id,
            'form' => $form,
            'updates' => $filtered,
        ]);

        return response()->json([
            'updates' => $filtered,
        ]);
    }
}
