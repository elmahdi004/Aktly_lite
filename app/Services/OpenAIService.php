<?php

namespace App\Services;

use App\Models\DirigeantsService;
use App\Models\ShareHolder;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\AddressService;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private $client;
    private $assistantId;
    private $defaultModel;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2',
            ],
        ]);

        $this->assistantId = config('services.openai.assistant_id');
        $this->defaultModel = 'gpt-4o';
    }

    
    public function getChatCompletionWithAssistant(string $message): ?string
    {
        if (empty(config('services.openai.api_key'))) {
            Log::error('OpenAI API key is missing.');
            return null;
        }

        if (empty($this->assistantId)) {
            return $this->getChatCompletion($message, $this->defaultModel);
        }

        try {
            $threadResponse = $this->client->post('threads', [
                'json' => [
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $message,
                        ],
                    ],
                ],
            ]);

            $threadData = json_decode($threadResponse->getBody(), true);
            $threadId = $threadData['id'];

            $runResponse = $this->client->post("threads/{$threadId}/runs", [
                'json' => [
                    'assistant_id' => $this->assistantId,
                ],
            ]);

            $runData = json_decode($runResponse->getBody(), true);
            $runId = $runData['id'];

            do {
                sleep(1);
                $statusResponse = $this->client->get("threads/{$threadId}/runs/{$runId}");
                $statusData = json_decode($statusResponse->getBody(), true);
                $status = $statusData['status'];
            } while ($status !== 'completed' && $status !== 'failed' && $status !== 'cancelled');

            $messagesResponse = $this->client->get("threads/{$threadId}/messages");
            $messagesData = json_decode($messagesResponse->getBody(), true);

            foreach (array_reverse($messagesData['data']) as $msg) {
                if ($msg['role'] === 'assistant') {
                    return $msg['content'][0]['text']['value'] ?? null;
                }
            }

            return null;

        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : null;
            \Log::error('Erreur OpenAI Assistant', [
                'message' => $e->getMessage(),
                'status' => $status,
                'body' => $body,
            ]);
            return null;
        } catch (\Exception $e) {
            \Log::error('Erreur OpenAI Assistant : ' . $e->getMessage());
            return null;
        }
    }

    public function getChatCompletion($message, $model = null)
    {
        $model = $model ?: $this->defaultModel;

        try {
            $client = new Client([
                'base_uri' => 'https://api.openai.com/v1/',
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                ]
            ]);
            $response = $client->post('chat/completions', [
                'json' => [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $message]
                    ],
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['choices'][0]['message']['content'] ?? null;
        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : null;
            \Log::error('Erreur API OpenAI', [
                'message' => $e->getMessage(),
                'status' => $status,
                'body' => $body,
            ]);
            return null;
        } catch (\Exception $e) {
            \Log::error('Erreur API OpenAI : ' . $e->getMessage());
            return null;
        }
    }

    public function suggestPdfFieldUpdates(array $fields, string $instruction, string $language = 'fr'): array
    {
        if (empty(config('services.openai.api_key'))) {
            Log::error('OpenAI API key is missing.');
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
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $this->defaultModel,
                    'temperature' => 0.2,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => json_encode($user, JSON_UNESCAPED_UNICODE)],
                    ],
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $content = $data['choices'][0]['message']['content'] ?? '';
            if (!is_string($content) || trim($content) === '') {
                return [];
            }

            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                Log::error('OpenAI response is not valid JSON', [
                    'response' => $content,
                ]);
                return [];
            }

            return $decoded;
        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : null;
            Log::error('OpenAI API error', [
                'message' => $e->getMessage(),
                'status' => $status,
                'body' => $body,
            ]);
            return [];
        } catch (\Throwable $e) {
            Log::error('OpenAI API error: ' . $e->getMessage());
            return [];
        }
    }

    public function stripAsterisks(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }
        return preg_replace('/[\*\x{2022}\x{2027}\x{2219}\x{25CF}\x{2605}]+/u', '', $text);
    }

    public function stripLegalFormHeader(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $cleaned = preg_replace('/^.*\bELP\b.*(\R)?/mi', '', $text);
        return ltrim($cleaned ?? '');
    }

    public function sanitizeDocumentText(?string $text): ?string
    {
        $cleaned = $this->stripAsterisks($text);
        return $this->stripLegalFormHeader($cleaned);
    }

    public function sanitizePvText(?string $text): ?string
    {
        $cleaned = $this->sanitizeDocumentText($text);
        if ($cleaned === null) {
            return null;
        }

        $pattern = '/(proc(?:e|\x{00E8})s[\s\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}]*verbal|proces[\s\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}]*verbaal)/iu';
        if (preg_match($pattern, $cleaned, $match, PREG_OFFSET_CAPTURE)) {
            $pos = $match[0][1];
            $cleaned = ltrim(substr($cleaned, $pos));
            return $cleaned;
        }

        $fallbacks = [
            'Procès-verbal',
            'Procès verbal',
            'Proces-verbaal',
            'Proces verbaal',
            'Proces-verbal',
            'Proces verbal',
        ];
        foreach ($fallbacks as $needle) {
            $pos = mb_stripos($cleaned, $needle);
            if ($pos !== false) {
                $cleaned = ltrim(mb_substr($cleaned, $pos));
                return $cleaned;
            }
        }

        return $cleaned;
    }


    public function improveDocumentText(string $text, string $language, ?string $instruction = null): ?string
    {
        $langue = $language === 'nl' ? 'nl' : 'fr';
        $instruction = trim((string) $instruction);
        $instructionBlock = '';
        if ($instruction !== '') {
            $instructionBlock = $langue === 'nl'
                ? "Gebruikersinstructie: {$instruction}\n\n"
                : "Consigne utilisateur : {$instruction}\n\n";
        }
        $prompt = $langue === 'nl'
            ? "Verbeter de volgende juridische tekst (PV/uittreksel). Behoud alle feiten, cijfers, namen en datums. Corrigeer taal, stijl en helderheid. Geen markdown, alleen platte tekst. {$instructionBlock}Tekst:\n\n{$text}"
            : "Améliore le texte juridique suivant (PV/extrait). Conserve tous les faits, chiffres, noms et dates. Corrige la langue, le style et la clarté. Pas de markdown, texte brut uniquement. {$instructionBlock}Texte :\n\n{$text}";

        return $this->getChatCompletion($prompt, $this->defaultModel);
    }


    public function buildMessage($demande)
    {
        // Extract BCE data - handle both JSON string and already decoded formats
        if (is_string($demande->bce_data)) {
            $bceData = json_decode($demande->bce_data, true);
        } else {
            $bceData = $demande->bce_data;
        }
        
        // Ensure it's an array
        if (!is_array($bceData)) {
            $bceData = (array)$bceData;
        }
        
        // Build structured data object
        $donneesActe = $this->buildDonneesActe($demande, $bceData);
        
        // Build comprehensive prompt
        $prompt = $this->buildComprehensivePrompt($donneesActe, $demande);
        
        \Log::info('Message complet envoyé à assistant IA', [
            'demande_id' => $demande->id,
            'langue' => $demande->langue_entreprise,
            'message_length' => strlen($prompt)
        ]);
        
        return $prompt;
    }

    public function getMissingFields($demande): array
    {
        if (is_string($demande->bce_data)) {
            $bceData = json_decode($demande->bce_data, true);
        } else {
            $bceData = $demande->bce_data;
        }

        if (!is_array($bceData)) {
            $bceData = (array)$bceData;
        }

        $donnees = $this->buildDonneesActe($demande, $bceData);
        $missing = [];

        if (empty($demande->date_assemblee)) {
            $missing[] = [
                'key' => 'date_assemblee',
                'type' => 'date',
                'label_fr' => "Date de l’assemblée générale",
                'label_nl' => "Datum van de algemene vergadering",
                'route' => 'step3',
                'step_label_fr' => "Étape 3 – Date et lieu",
                'step_label_nl' => "Stap 3 – Datum en plaats",
            ];
        }

        if (empty($demande->date_changement)) {
            $missing[] = [
                'key' => 'date_changement',
                'type' => 'date',
                'label_fr' => "Date du changement",
                'label_nl' => "Wijzigingsdatum",
                'route' => 'step3',
                'step_label_fr' => "Étape 3 – Date et lieu",
                'step_label_nl' => "Stap 3 – Datum en plaats",
            ];
        }

        if (empty($demande->fait_a)) {
            $missing[] = [
                'key' => 'fait_a',
                'type' => 'text',
                'label_fr' => "Lieu (fait à)",
                'label_nl' => "Plaats (opgemaakt te)",
                'route' => 'step3',
                'step_label_fr' => "Étape 3 – Date et lieu",
                'step_label_nl' => "Stap 3 – Datum en plaats",
            ];
        }

        if (empty($demande->nb_total_actions) || (int)$demande->nb_total_actions <= 0) {
            $missing[] = [
                'key' => 'nb_total_actions',
                'type' => 'number',
                'label_fr' => "Nombre total d’actions/parts",
                'label_nl' => "Totaal aantal aandelen/vennootschapsdelen",
                'route' => 'step3',
                'step_label_fr' => "Étape 3 – Date et lieu",
                'step_label_nl' => "Stap 3 – Datum en plaats",
            ];
        }

        if ($demande->address_service) {
            $nouvelleAdresse = $donnees['transfert_siege']['nouvelle_adresse'] ?? '';
            if (empty(trim((string)$nouvelleAdresse))) {
                $missing[] = [
                    'key' => 'transfert_siege',
                    'type' => 'info',
                    'message_fr' => "Adresse de transfert manquante. Complétez l’étape Adresse.",
                    'message_nl' => "Adres voor zetelverplaatsing ontbreekt. Vul de stap Adres aan.",
                    'route' => 'addressService',
                    'step_label_fr' => "Étape Adresse",
                    'step_label_nl' => "Stap Adres",
                ];
            }
        }

        if ($demande->cession_parts_service && empty($donnees['cessions'])) {
            $missing[] = [
                'key' => 'cessions',
                'type' => 'info',
                'message_fr' => "Cession de parts manquante. Complétez l’étape Cession de parts.",
                'message_nl' => "Aandelenoverdracht ontbreekt. Vul de stap Overdracht van aandelen in.",
                'route' => 'cessionService',
                'step_label_fr' => "Étape Cession de parts",
                'step_label_nl' => "Stap Overdracht van aandelen",
            ];
        }

        if ($demande->dirigeants_service
            && empty($donnees['administrateurs_nommes'])
            && empty($donnees['administrateurs_demissionnaires'])) {
            $missing[] = [
                'key' => 'dirigeants',
                'type' => 'info',
                'message_fr' => "Changements de dirigeants manquants. Complétez l’étape Dirigeants.",
                'message_nl' => "Wijzigingen van bestuurders ontbreken. Vul de stap Bestuurders in.",
                'route' => 'dirigeantsService',
                'step_label_fr' => "Étape Dirigeants",
                'step_label_nl' => "Stap Bestuurders",
            ];
        }

        return $missing;
    }

    /**
     * Build structured data object (donnees_acte) with all required information
     */
    private function buildDonneesActe($demande, $bceData)
    {
        // Extract company name - try multiple possible keys
        $companyName = $bceData['denomination'] ?? 
                      $bceData['denominationFR'] ?? 
                      $bceData['denominationNL'] ?? 
                      ($bceData['denomination'] ?? '');
        
        // Extract company type
        $companyType = $bceData['typeOfEnterprise'] ?? '';
        
        // Extract address - BCE data might have different structures
        $address = $bceData['address'] ?? 
                  $bceData['addresses'][0] ?? 
                  $bceData['siege'] ?? 
                  [];
        
        $donnees = [
            'societe' => [
                'nom' => $companyName,
                'forme' => $companyType,
                'numero_entreprise' => $demande->enterprise_number ?? '',
                'adresse_siege_actuel' => $this->formatAddress($address),
            ],
            'assemblee' => [
                'date' => $demande->date_assemblee ?? '',
                'date_changement' => $demande->date_changement ?? '',
                'lieu' => $demande->fait_a ?? '',
                'heure_debut' => '',
                'heure_fin' => '',
            ],
            'capital' => [
                'total_actions' => $demande->nb_total_actions ?? 0,
            ],
            'services' => [],
            'transfert_siege' => null,
            'administrateurs_demissionnaires' => [],
            'administrateurs_nommes' => [],
            'cessions' => [],
            'participants' => [],
            'signataires' => [],
        ];

        // 1) Objet acte - Services choisis
        $services = [];
        if ($demande->address_service) {
            $services[] = 'Transfert de siège social';
        }
        if ($demande->cession_parts_service) {
            $services[] = 'Cession de parts';
        }
        if ($demande->dirigeants_service) {
            $services[] = 'Démission(s)';
            $services[] = 'Nomination(s)';
        }
        $donnees['services'] = $services;

        // 5) Si changement adresse
        if ($demande->address_service) {
            $addressService = AddressService::where('demande_id', $demande->id)->first();
            if ($addressService) {
                $donnees['transfert_siege'] = [
                    'ancienne_adresse' => $addressService->an_address ?? '',
                    'nouvelle_adresse' => $this->formatAddress([
                        'street' => $addressService->rue ?? '',
                        'houseNumber' => $addressService->numero ?? '',
                        'box' => $addressService->box ?? '',
                        'zipCode' => $addressService->zip_code ?? '',
                        'municipality' => $addressService->localite ?? '',
                        'country' => $addressService->pays ?? '',
                    ]),
                    'date_effet' => $demande->date_changement ?? '',
                ];
            }
        }

        // 6) Si changement dirigeants
        if ($demande->dirigeants_service) {
            $nvDirigeants = DirigeantsService::where('demande_id', $demande->id)->get();
            $oldDirigeants = [];
            
            // Extract old dirigeants from BCE data
            $functions = $bceData['function'] ?? [];
            if (!is_array($functions)) {
                $functions = [$functions];
            }
            Log::info('functions', ['data' => $functions]);
            foreach ($functions as $function) {
                // Handle both object and array formats
                $functionData = is_object($function) ? $function : (object)$function;
                
                if (isset($functionData->person)) {
                    $person = is_object($functionData->person) ? $functionData->person : (object)$functionData->person;
                $oldDirigeants[] = [
                        'surname' => $person->surname ?? '',
                        'givenName' => $person->givenName ?? '',
                    ];
                }
            }
            Log::info('old dirigeants', ['data' => $oldDirigeants]);
            // Find added dirigeants (nommés)
            $addedDirigeants = [];
            foreach ($nvDirigeants as $nvDirigeant) {
                // $isNew = true;
                // foreach ($oldDirigeants as $oldDirigeant) {
                //     if (($nvDirigeant->surname ?? '') === ($oldDirigeant['surname'] ?? '') &&
                //         ($nvDirigeant->givenName ?? '') === ($oldDirigeant['givenName'] ?? '')) {
                //         $isNew = false;
                //         break;
                //     }
                // }
                // Log::info('old old :',$nvDirigeant);
                if ($nvDirigeant->is_modified == 1) {
                    $addedDirigeants[] = [
                        'prenom' => $nvDirigeant->givenName ?? '',
                        'nom' => $nvDirigeant->surname ?? '',
                    ];
                }
            }

            // Find removed dirigeants (démissionnaires)
            $removedDirigeants = [];
            foreach ($nvDirigeants as $nvDirigeant) {
                $isDeleted = data_get($nvDirigeant, 'is_deleted', 0);
                if ((int)$isDeleted === 1) {
                    Log::info('old old elmahdi:', ['dirigeant' => (array) $nvDirigeant]);
                    $removedDirigeants[] = [
                        'prenom' => data_get($nvDirigeant, 'givenName', ''),
                        'nom' => data_get($nvDirigeant, 'surname', ''),
                    ];
                }
            }

            $donnees['administrateurs_nommes'] = $addedDirigeants;
            $donnees['administrateurs_demissionnaires'] = $removedDirigeants;
            Log::info('old dirigeants got it : ',$donnees['administrateurs_demissionnaires']);
            Log::info('new dirigeants got it : ',$donnees['administrateurs_nommes']);
        }

        // 7) Si cession parts
        Log::info('Cession parts service check', [
            'demande_id' => $demande->id,
            'cession_parts_service' => $demande->cession_parts_service,
        ]);
        if ($demande->cession_parts_service) {
            Log::info('Cession parts service detected.');
            $cessionParts = ShareHolder::where('demande_id', $demande->id)->get();
            Log::info('Cession parts data: ' . json_encode($cessionParts));
            $cessions = [];
            $repartitionActionnaires = [];
            
            // Separate cédants and acquéreurs
            $cedants = [];
            $acquereurs = [];
            
            foreach ($cessionParts as $cession) {
                $sharesOld = (int)($cession->shares_old ?? 0);
                $shares = (int)($cession->shares ?? 0);

                $repartitionActionnaires[] = [
                    'nom' => $cession->full_name ?? '',
                    'avant' => $sharesOld,
                    'apres' => $shares,
                ];
                
                if ($shares < $sharesOld) {
                    // Cédant
                    $cedants[] = [
                        'nom' => $cession->full_name ?? '',
                        'actions_cedees' => $sharesOld - $shares,
                    ];
                } elseif ($shares >= $sharesOld) {
                    // Acquéreur
                    $acquereurs[] = [
                        'nom' => $cession->full_name ?? '',
                        'actions_recues' => $shares - $sharesOld,
                    ];
                }
            }
            Log::info('Cedants: ' . json_encode($cedants));
            Log::info('Acquereurs: ' . json_encode($acquereurs));
            // Match cédants with acquéreurs (simple pairing)
            $maxPairs = max(count($cedants), count($acquereurs));
            for ($i = 0; $i < $maxPairs; $i++) {
                $cession = [
                    'cedant' => isset($cedants[$i]) ? [
                        'nom' => $cedants[$i]['nom'],
                        'quantite' => $cedants[$i]['actions_cedees'],
                    ] : null,
                    'acquereur' => isset($acquereurs[$i]) ? [
                        'nom' => $acquereurs[$i]['nom'],
                        'quantite' => $acquereurs[$i]['actions_recues'],
                    ] : null,
                ];
                
                if ($cession['cedant'] || $cession['acquereur']) {
                    $cessions[] = $cession;
                }
                Log::info('Processing cession part: ' . json_encode($cessions));
            }
            
            $donnees['cessions'] = $cessions;
            $donnees['repartition_parts'] = [
                'total_actions' => $demande->nb_total_actions ?? 0,
                'actionnaires' => $repartitionActionnaires,
            ];
        }

        // 9) Signataires - tous les administrateurs à jour
        $allDirigeants = DirigeantsService::where('demande_id', $demande->id)->get();
        $signataires = [];
        foreach ($allDirigeants as $dirigeant) {
            $signataires[] = ($dirigeant->givenName ?? '') . ' ' . ($dirigeant->surname ?? '');
        }
        $donnees['signataires'] = $signataires;

        return $donnees;
    }

    /**
     * Format address from BCE data structure
     */
    private function formatAddress($address)
    {
        if (is_array($address) || is_object($address)) {
            $address = (array)$address;
            $parts = [];
            
            // Helper function to safely convert to string
            $toString = function($value) {
                if (is_array($value) || is_object($value)) {
                    // If it's an array/object, try to extract meaningful value
                    if (is_array($value)) {
                        // Try common keys
                        return $value['value'] ?? $value['text'] ?? $value['name'] ?? '';
                    } else {
                        // Object - try common properties
                        return $value->value ?? $value->text ?? $value->name ?? '';
                    }
                }
                return (string)$value;
            };
            
            if (!empty($address['street'])) {
                $street = $toString($address['street']);
                if ($street) {
                    $parts[] = $street;
                }
            }
            if (!empty($address['houseNumber'])) {
                $houseNumber = $toString($address['houseNumber']);
                if ($houseNumber) {
                    $parts[] = $houseNumber;
                }
            }
            if (!empty($address['box'])) {
                $box = $toString($address['box']);
                if ($box) {
                    $parts[] = 'bte ' . $box;
                }
            }
            if (!empty($address['zipCode'])) {
                $zipCode = $toString($address['zipCode']);
                if ($zipCode) {
                    $parts[] = $zipCode;
                }
            }
            if (!empty($address['municipality'])) {
                $municipality = $toString($address['municipality']);
                if ($municipality) {
                    $parts[] = $municipality;
                }
            }
            if (!empty($address['country'])) {
                $country = $toString($address['country']);
                if ($country) {
                    $parts[] = $country;
                }
            }
            
            return implode(' ', array_filter($parts));
        }
        return '';
    }

    /**
     * Build comprehensive prompt following the detailed instructions
     */
    private function buildComprehensivePrompt($donneesActe, $demande)
    {
        $langue = $demande->langue_entreprise === 'nl' ? 'nl' : 'fr';
        $isSRL = stripos($donneesActe['societe']['forme'] ?? '', 'SRL') !== false || 
                 stripos($donneesActe['societe']['forme'] ?? '', 'BV') !== false;
        $termeActions = $isSRL ? ($langue === 'nl' ? 'maatschappelijke aandelen' : 'parts sociales') : 
                                 ($langue === 'nl' ? 'aandelen' : 'actions');

        $pvTemplate = $this->getPVTemplate($langue);

        $prompt = <<<PROMPT
Tu es un assistant juridique expert en rédaction d'actes officiels et de publications légales belges.

Tu vas recevoir :
1️⃣ Un bloc JSON intitulé `donnees_acte` contenant toutes les informations nécessaires.
2️⃣ Un texte modèle intitulé `modele_PV` représentant le gabarit du procès-verbal.
3️⃣ Un paramètre `langue` dont la valeur est "{$langue}".

---

### Tâche à exécuter

#### Génération du procès-verbal (PV)
- Utilise le contenu de `modele_PV` comme base.
- Remplace chaque variable ou placeholder par les valeurs correspondantes provenant de `donnees_acte`.
- Inclue uniquement les sections pertinentes selon les données disponibles : cession de parts, transfert de siège social, démission, nomination.
- Si une information est absente, omets simplement la section correspondante sans inventer de texte.
- Rédige le PV dans la langue indiquée par le paramètre `langue` ({$langue}).
- Emploie les bons termes selon la forme juridique : {$termeActions}

---

### Sortie attendue
Rends **uniquement** le procès-verbal finalisé (format Word-ready), prêt à être utilisé directement.

---

### ⚖️ Règles impératives
- Ne jamais inventer ni interpréter une information absente.
- Conserver fidèlement les noms, adresses et dates.
- Ne mentionne pas la forme juridique (ex. ELP) ni d'en-tête société ; le PV doit commencer directement par « Procès-verbal » / « Proces-verbaal ».
- Produire un style juridique formel et conforme aux usages belges.
- Le texte doit être complet et prêt à être utilisé.
- **Ne pas utiliser de markdown, ne pas utiliser d’astérisques (*), ne pas mettre de gras. Sortie en texte brut uniquement.**

---

### donnees_acte (JSON)
```json
{$this->formatJsonForPrompt($donneesActe)}
```

---

### modele_PV
{$pvTemplate}

---

Génère maintenant le procès-verbal complet en respectant strictement toutes les instructions ci-dessus.
PROMPT;

        return $prompt;
    }

    /**
     * Get PV template based on language
     */
    private function getPVTemplate($langue)
    {
        if ($langue === 'fr') {
            $filePath = resource_path('openai/templates/pv_template_fr.txt');
            if (file_exists($filePath)) {
                $template = file_get_contents($filePath);
                if ($template !== false && trim($template) !== '') {
                    return $template;
                }
            }
        }

        if ($langue === 'nl') {
            $filePath = resource_path('openai/templates/pv_template_nl.txt');
            if (file_exists($filePath)) {
                $template = file_get_contents($filePath);
                if ($template !== false && trim($template) !== '') {
                    return $template;
                }
            }
            return <<<TEMPLATE
Proces-verbaal van de buitengewone algemene vergadering van {{assemblee.date|nl}}

Cessie van aandelen, overdracht van maatschappelijke zetel, ontslag en benoeming van bestuurder.

De buitengewone algemene vergadering van de vennootschap werd gehouden te {{assemblee.lieu}} op {{assemblee.date|nl}} om {{assemblee.heure_debut}} in aanwezigheid van:
{{#participants}}
- {{civilite}} {{nom}}, {{actions}} aandelen
{{/participants}}

Het volledige kapitaal is vertegenwoordigd ({{capital.total_actions}} aandelen), de vergadering kan geldig beslissen over de agenda:
- Cessie van aandelen
- Overdracht van maatschappelijke zetel
- Ontslag van de bestuurder
- Benoeming van de bestuurder

Besluiten:

{{#cessions.length}}
1. Cessie van aandelen
{{#cessions}}
- {{cedant}} cedeert {{quantite}} aandelen aan {{acquereur}}, die aanvaardt.
{{/cessions}}
{{/cessions.length}}

{{#transfert_siege.nouvelle_adresse}}
2. Overdracht van maatschappelijke zetel
De maatschappelijke zetel wordt overgedragen naar {{transfert_siege.nouvelle_adresse}}, met ingang op {{transfert_siege.date_effet|nl}}.
{{/transfert_siege.nouvelle_adresse}}

{{#administrateurs_demissionnaires.length}}
3. Ontslag van de bestuurder
Het ontslag van {{#administrateurs_demissionnaires}}{{.}}{{^last}}, {{/last}}{{/administrateurs_demissionnaires}} uit zijn functie van bestuurder, waarbij hem kwijting wordt verleend zonder voorbehoud, met ingang op {{assemblee.date|nl}}.
{{/administrateurs_demissionnaires.length}}

{{#administrateurs_nommes.length}}
4. Benoeming van een bestuurder
Deze buitengewone algemene vergadering aanvaardt de benoeming van {{#administrateurs_nommes}}{{.}}{{^last}}, {{/last}}{{/administrateurs_nommes}} tot de functie van bestuurder, die aanvaardt, met ingang op {{assemblee.date|nl}}.
{{/administrateurs_nommes.length}}

De agenda is uitgeput, de vergadering wordt gesloten om {{assemblee.heure_fin}}.

Handtekeningen:
{{#signataires}}
- {{.}}
{{/signataires}}
TEMPLATE;
        }

        return <<<TEMPLATE
    Procès-verbal de l'assemblée générale extraordinaire du {{assemblee.date|fr}}

Cession de parts, transfert de siège social, démission et nomination d'administrateur.

L'assemblée générale extraordinaire de la société a été réunie au {{assemblee.lieu}} le {{assemblee.date|fr}} à {{assemblee.heure_debut}} en présence de :
{{#participants}}
- {{civilite}} {{nom}}, {{actions}} actions
{{/participants}}

L'ensemble du capital étant réuni ({{capital.total_actions}} actions), l'assemblée peut valablement statuer sur l'ordre du jour :
- Cession de parts
- Transfert de siège social
- Démission de l'administrateur
- Nomination de l'administrateur

Résolutions :

{{#cessions.length}}
1. Cession d'actions
{{#cessions}}
- {{cedant}} cède {{quantite}} actions à {{acquereur}}, lequel/laquelle accepte.
{{/cessions}}
{{/cessions.length}}

{{#transfert_siege.nouvelle_adresse}}
2. Transfert de siège social
Le siège social est transféré à {{transfert_siege.nouvelle_adresse}}, avec effet au {{transfert_siege.date_effet|fr}}.
{{/transfert_siege.nouvelle_adresse}}

{{#administrateurs_demissionnaires.length}}
3. Démission de l'administrateur
La démission de {{#administrateurs_demissionnaires}}{{.}}{{^last}}, {{/last}}{{/administrateurs_demissionnaires}} de son poste d'administrateur lui donnant décharge de toute responsabilité sans réserve, avec effet au {{assemblee.date|fr}}.
{{/administrateurs_demissionnaires.length}}

{{#administrateurs_nommes.length}}
4. Nomination d'un administrateur
La dite assemblée générale extraordinaire accepte la nomination de {{#administrateurs_nommes}}{{.}}{{^last}}, {{/last}}{{/administrateurs_nommes}} au poste d'administrateur, lequel/laquelle accepte, avec effet au {{assemblee.date|fr}}.
{{/administrateurs_nommes.length}}

L'ordre du jour étant épuisé, l'assemblée est levée à {{assemblee.heure_fin}}.

Signatures :
{{#signataires}}
- {{.}}
{{/signataires}}
TEMPLATE;
    }

    /**
     * Get extrait template based on language
     */
    private function getExtraitTemplate($langue)
    {
        if ($langue === 'fr') {
            $filePath = resource_path('openai/templates/extrait_template_fr.txt');
            if (file_exists($filePath)) {
                $template = file_get_contents($filePath);
                if ($template !== false && trim($template) !== '') {
                    return $template;
                }
            }
        }

        if ($langue === 'nl') {
            $filePath = resource_path('openai/templates/extrait_template_nl.txt');
            if (file_exists($filePath)) {
                $template = file_get_contents($filePath);
                if ($template !== false && trim($template) !== '') {
                    return $template;
                }
            }
            return <<<TEMPLATE
Rédige un **extrait destiné à la publication au Moniteur belge (Formulaire I)** en **néerlandais**, **uniquement** sur base du procès-verbal (PV) ci-dessous.

### Règles IMPÉRATIVES de sortie
- **Aucun en-tête** : ne mentionne pas la dénomination, l'adresse, ni le n° d'entreprise (ces infos figurent déjà dans le formulaire).
- **Aucun titre/préambule** : commence directement par « Bij besluiten van de buitengewone algemene vergadering gehouden op [datum]… ».
- **Aucune remarque / note** : pas de conseils, pas de texte explicatif.
- **Aucune mention "Handtekening"** : le signataire signe au verso. Conclus par « Voor overeenkomstig uittreksel » suivi du nom et de la qualité.
- Respecte **strictement** les informations du PV (n'invente rien ; si une info manque, omets simplement la ligne).
- Pour une **SRL/BV**, emploie « maatschappelijke aandelen » ; pour une **SA/NV**, « aandelen ». Conserve l'orthographe des noms/adresses et les dates telles qu'indiquées.
- Ne pas utiliser de markdown, ne pas utiliser d’astérisques (*), ne pas mettre de gras. Sortie en texte brut uniquement.

### Contenu attendu (dans cet ordre si présent dans le PV)
1) **Cessie van maatschappelijke aandelen / aandelen** : cedent → cessionaris (adres indien in het PV) + nieuwe verdeling (ex. « Dhr. X : 100 maatschappelijke aandelen »).
2) **Overdracht van de maatschappelijke zetel** : nieuw adres + **datum van ingang**.
3) **Ontslag** : identiteit van de uitgetredene + functie + **datum van ingang** + kwijting indien vermeld.
4) **Benoeming** : identiteit van de benoemde + functie + adres indien aanwezig + **datum van ingang**.

### Forme & style
- Style synthétique, en listes numérotées.
- Terminer par :
« Gedaan te [stad uit PV indien aanwezig], op [datum van PV].
**Voor overeenkomstig uittreksel**
[Naam en hoedanigheid van de bevoegde ondertekenaar] »
TEMPLATE;
        }

        return <<<TEMPLATE
Rédige un **extrait destiné à la publication au Moniteur belge (Formulaire I)** en **français**, **uniquement** sur base du procès-verbal (PV) ci-dessous.

### Règles IMPÉRATIVES de sortie
- **Aucun en-tête** : ne mentionne pas la dénomination, l'adresse, ni le n° d'entreprise (ces infos figurent déjà dans le formulaire).
- **Aucun titre/préambule** : commence directement par « Par décisions de l'assemblée générale extraordinaire tenue le [date]… ».
- **Aucune remarque / note** : pas de conseils, pas de texte explicatif.
- **Aucune mention "Signature"** : le signataire signe au verso. Conclus par « Pour extrait conforme » suivi du nom et de la qualité.
- Respecte **strictement** les informations du PV (n'invente rien ; si une info manque, omets simplement la ligne).
- Pour une **SRL**, emploie « parts sociales » ; pour une **SA**, « actions ». Conserve l'orthographe des noms/adresses et les dates telles qu'indiquées.
- Ne pas utiliser de markdown, ne pas utiliser d’astérisques (*), ne pas mettre de gras. Sortie en texte brut uniquement.

### Contenu attendu (dans cet ordre si présent dans le PV)
1) **Cession de parts sociales / actions** : cédant → cessionnaire (adresse si dans le PV) + nouvelle répartition (ex. « M. X : 100 parts sociales »).
2) **Transfert du siège social** : nouvelle adresse + **date d'effet**.
3) **Démission** : identité du sortant + fonction + **date d'effet** + décharge si mentionnée.
4) **Nomination** : identité du nommé + fonction + adresse si présente + **date d'effet**.

### Forme & style
- Style synthétique, en listes numérotées.
- Terminer par :
« Fait à [ville extraite du PV si présente], le [date du PV].
**Pour extrait conforme**
[Nom et qualité du signataire habilité] »
TEMPLATE;
    }

    /**
     * Format JSON for prompt (pretty print, but compact for prompt)
     */
    private function formatJsonForPrompt($data)
    {
        // Clean data to ensure all values are JSON-serializable
        $cleanedData = $this->cleanDataForJson($data);
        return json_encode($cleanedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Recursively clean data to ensure all values are JSON-serializable
     */
    private function cleanDataForJson($data)
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                $cleaned[$key] = $this->cleanDataForJson($value);
            }
            return $cleaned;
        } elseif (is_object($data)) {
            // Convert object to array and clean
            return $this->cleanDataForJson((array)$data);
        } elseif (is_string($data) || is_numeric($data) || is_bool($data) || is_null($data)) {
            return $data;
        } else {
            // For any other type, convert to string
            return (string)$data;
        }
    }
}
