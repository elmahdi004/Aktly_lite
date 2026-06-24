<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private Client $client;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.claude.api_key');
        $this->model = (string) config('services.claude.model', 'claude-3-5-sonnet-latest');
        $this->client = new Client([
            'base_uri' => 'https://api.anthropic.com/v1/',
            'headers' => [
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ],
            'timeout' => 30,
        ]);
    }

    public function suggestPdfFieldUpdates(array $fields, string $instruction, string $language = 'fr'): array
    {
        if ($this->apiKey === '') {
            Log::error('Claude API key is missing.');
            return [];
        }

        $language = $language === 'nl' ? 'nl' : 'fr';
        $instruction = trim($instruction);
        if ($instruction === '') {
            return [];
        }

        $fieldList = array_map(function (array $field) use ($language) {
            return [
                'key' => $field['key'] ?? null,
                'label' => $language === 'nl' ? ($field['label_nl'] ?? '') : ($field['label_fr'] ?? ''),
                'type' => $field['type'] ?? 'text',
                'value' => $field['value'] ?? null,
            ];
        }, $fields);

        $system = 'You update PDF form fields based only on the user instruction and the provided fields. '
            . 'Never invent facts, names, numbers, addresses, dates, or identifiers. '
            . 'Only return JSON. Do not include markdown.';

        $user = [
            'instruction' => $instruction,
            'rules' => [
                'Return a JSON array of objects: [{"key":"...","value":"..."}].',
                'Only include fields that should change based on the instruction.',
                'If no change is needed, return an empty array [].',
                'Use YYYY-MM-DD format for date fields.',
                'Do not guess missing values.',
            ],
            'fields' => $fieldList,
        ];

        try {
            $response = $this->client->post('messages', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                ],
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => 1000,
                    'temperature' => 0.2,
                    'system' => $system,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => json_encode($user, JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $content = $data['content'][0]['text'] ?? '';
            if (!is_string($content) || trim($content) === '') {
                return [];
            }

            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                Log::error('Claude response is not valid JSON', [
                    'response' => $content,
                ]);
                return [];
            }

            return $decoded;
        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : null;
            Log::error('Claude API error', [
                'message' => $e->getMessage(),
                'status' => $status,
                'body' => $body,
            ]);
            return [];
        } catch (\Throwable $e) {
            Log::error('Claude API error: ' . $e->getMessage());
            return [];
        }
    }
}
