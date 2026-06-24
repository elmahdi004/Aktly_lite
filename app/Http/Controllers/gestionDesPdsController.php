<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Services\IdAnalyzerService;
use App\Models\Depositaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class gestionDesPdsController extends Controller
{

    private function normalizeVerificationStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        return match ($status) {
            'accept', 'accepted', 'approved', 'success' => 'approved',
            'reject', 'rejected', 'declined', 'refused' => 'declined',
            default => 'pending',
        };
    }

    private function extractDecisionDetails(array $decision): array
    {
        $decisionData = $decision['data'] ?? ($decision['finalTransaction']['data'] ?? null);
        $data = is_array($decisionData) ? $decisionData : [];
        $verification = is_array($decision['verification'] ?? null) ? $decision['verification'] : [];
        $person = is_array($data['person'] ?? null) ? $data['person'] : [];
        $document = is_array($data['document'] ?? null) ? $data['document'] : [];
        $address = is_array($data['address'] ?? null) ? $data['address'] : [];

        $normalize = fn($value) => is_string($value) ? trim($value) : $value;
        $getDataValue = fn($key) => $normalize($data[$key] ?? null);

        $buildAddress = function (array $addressData): ?string {
            $parts = [];
            foreach (['street', 'streetNumber', 'houseNumber', 'unit', 'zipcode', 'city', 'state', 'country'] as $key) {
                $value = $addressData[$key] ?? null;
                if (is_string($value)) {
                    $value = trim($value);
                }
                if (!empty($value)) {
                    $parts[] = $value;
                }
            }
            return $parts ? implode(' ', $parts) : null;
        };

        $details = [
            'firstName' => $normalize($person['firstName'] ?? $person['givenName'] ?? '') ?: $getDataValue('firstName'),
            'lastName' => $normalize($person['lastName'] ?? $person['surname'] ?? $person['familyName'] ?? '') ?: $getDataValue('lastName'),
            'gender' => $normalize($person['gender'] ?? $verification['gender'] ?? '') ?: $getDataValue('gender'),
            'dateOfBirth' => $normalize($person['dateOfBirth'] ?? $person['dob'] ?? '') ?: $getDataValue('dob'),
            'placeOfBirth' => $normalize($person['placeOfBirth'] ?? '') ?: $getDataValue('placeOfBirth'),
            'documentNumber' => $normalize($document['documentNumber'] ?? $document['documentNo'] ?? $verification['documentNumber'] ?? '')
                ?: $getDataValue('document'),
            'personalNumber' => $normalize($document['personalNumber'] ?? $document['personalNo'] ?? $person['personalNumber'] ?? '')
                ?: $getDataValue('personalNumber'),
            'documentType' => $normalize($document['type'] ?? $document['documentType'] ?? $verification['documentType'] ?? '')
                ?: $getDataValue('documentType'),
            'issuingCountry' => $normalize($document['issuingCountry'] ?? $document['issuer'] ?? $document['country'] ?? '')
                ?: $getDataValue('countryFull'),
            'issueDate' => $normalize($document['issueDate'] ?? $document['issue'] ?? $document['issued'] ?? '')
                ?: $getDataValue('issueDate'),
            'expiryDate' => $normalize($document['expiryDate'] ?? $document['expireDate'] ?? $document['expiry'] ?? '')
                ?: $getDataValue('expiry'),
            'address' => $normalize($buildAddress($address))
                ?: $getDataValue('address'),
        ];

        return array_filter($details, fn($value) => $value !== null && $value !== '');
    }

    private function fetchDecisionPayload(IdAnalyzerService $idAnalyzerService, string $sessionId): array
    {
        $decisionResponse = $idAnalyzerService->getDecision($sessionId);
        $decision = json_decode($decisionResponse->getContent(), true);
        if ($decision === null && method_exists($decisionResponse, 'getData')) {
            $decision = $decisionResponse->getData(true);
        }
        $decision = is_array($decision) ? $decision : [];

        $hasUsefulData = !empty($decision)
            && (!empty($decision['data'])
                || !empty($decision['verification'])
                || !empty($decision['finalTransaction'])
                || !empty($decision['outputImage'])
                || !empty($decision['intermediateTransactions']));

        if (!$hasUsefulData) {
            $transactionResponse = $idAnalyzerService->getTransactionData($sessionId);
            $decision = json_decode($transactionResponse->getContent(), true);
            if ($decision === null && method_exists($transactionResponse, 'getData')) {
                $decision = $transactionResponse->getData(true);
            }
            $decision = is_array($decision) ? $decision : [];
        }

        return $decision;
    }

    private function normalizeGender(?string $gender): ?string
    {
        $gender = strtolower(trim((string) $gender));
        return match ($gender) {
            'm', 'male', 'man' => 'M',
            'f', 'female', 'woman' => 'F',
            default => null,
        };
    }

    private function buildVerifiedPersonPayload(IdAnalyzerService $idAnalyzerService, ?string $sessionId, ?array $fallback): array
    {
        $fallback = is_array($fallback) ? $fallback : [];
        $fallbackPerson = is_array($fallback['person'] ?? null) ? $fallback['person'] : [];
        $fallback['person'] = $fallbackPerson;

        if (empty($sessionId)) {
            return $fallback;
        }

        $decision = $this->fetchDecisionPayload($idAnalyzerService, $sessionId);
        $status = $this->normalizeVerificationStatus($decision['verification']['status']
            ?? $decision['decision']
            ?? ($decision['finalTransaction']['decision'] ?? null));

        if ($status !== 'approved') {
            return $fallback;
        }

        $details = $this->extractDecisionDetails($decision);
        if (empty($details)) {
            return $fallback;
        }

        $person = $fallbackPerson;
        foreach (['firstName', 'lastName', 'dateOfBirth', 'placeOfBirth'] as $key) {
            if (!empty($details[$key])) {
                $person[$key] = $details[$key];
            }
        }

        $gender = $this->normalizeGender($details['gender'] ?? null);
        if (!empty($gender)) {
            $person['gender'] = $gender;
        }

        $idNumber = $details['personalNumber'] ?? $details['documentNumber'] ?? null;
        if (!empty($idNumber)) {
            $person['idNumber'] = $idNumber;
        }

        if (!empty($details['address'])) {
            $person['addresses'] = [
                ['fullAddress' => $details['address']],
            ];
        }

        $fallback['person'] = $person;
        return $fallback;
    }

    public function formulaire1(Request $request)
    {

        $demande_id = $request->query('demande_id');
        $data = Demande::with(['verification', 'shareholders.verification', 'dirigeants.verification', 'depositaire'])->find($demande_id);
        if (!$data) {
            abort(404, 'Demande not found');
        }
        $idAnalyzerService = new IdAnalyzerService();
        $requestUser = null;
        if ($data->verification && $data->verification->veriff_session) {
            $requestUser = $idAnalyzerService->getPersonData($data->verification->veriff_session)->getData(true);
            $requestUser = $this->buildVerifiedPersonPayload($idAnalyzerService, $data->verification->veriff_session, $requestUser);
        }

        // get details for shareholders users
        $shareholders = $data->shareholders
            ->filter(function ($shareholder) {
                return $shareholder->verification && $shareholder->verification->veriff_session;
            })
            ->map(function ($shareholder) {
                $idAnalyzerService = new IdAnalyzerService();
                $sessionId = $shareholder->verification->veriff_session;
                $fallback = $idAnalyzerService->getPersonData($sessionId)->getData(true);
                $shareholder->data = $this->buildVerifiedPersonPayload($idAnalyzerService, $sessionId, $fallback);
                return $shareholder;
            });
        // get details for dirigeants users 
        $dirigeants = $data->dirigeants
            ->map(function ($dirigeant) {
                if ($dirigeant->verification && $dirigeant->verification->veriff_session) {
                    $idAnalyzerService = new IdAnalyzerService();
                    $sessionId = $dirigeant->verification->veriff_session;
                    $fallback = $idAnalyzerService->getPersonData($sessionId)->getData(true);
                    $dirigeant->data = $this->buildVerifiedPersonPayload($idAnalyzerService, $sessionId, $fallback);
                } else {
                    $dirigeant->data = [
                        'person' => [
                            'firstName' => $dirigeant->givenName,
                            'lastName' => $dirigeant->surname,
                            'idNumber' => $dirigeant->rc ?: $dirigeant->carte_identite,
                        ],
                    ];
                }
                return $dirigeant;
            })
            ->filter(function ($dirigeant) {
                return (int) data_get($dirigeant, 'is_deleted', 0) === 1
                    || (int) data_get($dirigeant, 'is_modified', 0) === 1;
            })
            ->sortBy('created_at')
            ->values();
        $langue = $data->langue_entreprise;
        $rawText = $data->pub_text;
        if (is_array($rawText)) {
            $parts = [];
            foreach (['part1', 'part2', 'part3', 'part4'] as $key) {
                if (!empty($rawText[$key]) && is_string($rawText[$key])) {
                    $parts[] = $rawText[$key];
                }
            }
            if (!empty($parts)) {
                $rawText = implode('<br>', $parts);
            } else {
                $rawText = implode('<br>', array_map('strval', $rawText));
            }
        }
        $text1 = $this->wordwrap_alnum((string) ($rawText ?? ''));
        $text2 = $this->splitTextByLines($text1, 30);
        $data->pub_text = $text2;

        $pdfFieldsService = app(\App\Services\PdfFieldsService::class);
        $bceData = $pdfFieldsService->applyFormulaire1Overrides(json_decode($data->bce_data, true), $data);
        
        $attestation = $pdfFieldsService->buildAttestation($data);


        if ($langue == 'fr' && $data) {
            if ($request->query('download')) {
                return view('pdfs.fr.formulaire1', ['data' => $bceData, 'download' => true, 'demande' => $data,'requestUser'=>$requestUser, 'attestation' => $attestation]);
            } else {
                return view('pdfs.fr.formulaire1', ['data' => $bceData, 'demande' => $data,'requestUser'=>$requestUser, 'attestation' => $attestation]);
            }
        }else if($langue == 'nl' && $data){
            if($request->query('download')){
                return view('pdfs.nl.formulier1',['data' => $bceData,'download'=>true, 'demande' => $data,'requestUser'=>$requestUser, 'attestation' => $attestation]); 
            }else{
                return view('pdfs.nl.formulier1',['data' => $bceData, 'demande' => $data,'requestUser'=>$requestUser, 'attestation' => $attestation]);
            }
        } else {

        }
    }
    public function formulaire2(Request $request)
    {
        $idAnalyzerService = new IdAnalyzerService();
        $requestUser = null;
        $demande_id = $request->query('demande_id');

        $data = Demande::with(['verification', 'shareholders.verification', 'dirigeants.verification', 'address', 'depositaire'])->find($demande_id);
        $demande = $data; // $demande is just $data with address loaded
        $langue = $data->langue_entreprise;

        $pdfFieldsService = app(\App\Services\PdfFieldsService::class);
        $bceData = $pdfFieldsService->applyFormulaire2Overrides(json_decode($data->bce_data, true), $demande);
        $attestation = $pdfFieldsService->buildAttestation($demande);
        $demande->setAttribute('address', $pdfFieldsService->buildFormulaire2Address($demande));

        if ($data->verification && $data->verification->veriff_session) {
            $requestUser = $idAnalyzerService->getPersonData($data->verification->veriff_session)->getData(true);
            $requestUser = $this->buildVerifiedPersonPayload($idAnalyzerService, $data->verification->veriff_session, $requestUser);
        }
        // $shareholders = $data->shareholders
        //     ->filter(function ($shareholder) {
        //         return $shareholder->verification && $shareholder->verification->veriff_session;
        //     })
        //     ->map(function ($shareholder) {
        //         $veriffService = new VeriffService();
        //         $shareholder->data = $veriffService->getPersonData($shareholder->verification->veriff_session)->getData(true);
        //         return $shareholder;
        //     });
        $shareholders = $data->shareholders
            ->map(function ($shareholder) {
                $fullName = trim((string) $shareholder->full_name);
                $firstName = $fullName;
                $lastName = '';
                if ($fullName !== '') {
                    $parts = preg_split('/\s+/', $fullName, 2);
                    $firstName = $parts[0] ?? '';
                    $lastName = $parts[1] ?? '';
                }

                $shares = is_numeric($shareholder->shares) ? (float) $shareholder->shares : null;
                $sharesOld = is_numeric($shareholder->shares_old) ? (float) $shareholder->shares_old : null;
                $isDeleted = ($shares !== null && $shares <= 0) && ($sharesOld !== null && $sharesOld > 0);

                return [
                    'data' => [
                        'person' => [
                            'firstName' => $firstName,
                            'lastName' => $lastName,
                            'idNumber' => $shareholder->rc,
                        ],
                    ],
                    'function' => 'Actionnaire',
                    'is_deleted' => $isDeleted ? 1 : 0,
                ];
            });
        $dirigeants = $data->dirigeants
            ->map(function ($dirigeant) {
                if ($dirigeant->verification && $dirigeant->verification->veriff_session) {
                    $idAnalyzerService = new IdAnalyzerService();
                    $sessionId = $dirigeant->verification->veriff_session;
                    $fallback = $idAnalyzerService->getPersonData($sessionId)->getData(true);
                    $dirigeant->data = $this->buildVerifiedPersonPayload($idAnalyzerService, $sessionId, $fallback);
                } else {
                    $dirigeant->data = [
                        'person' => [
                            'firstName' => $dirigeant->givenName,
                            'lastName' => $dirigeant->surname,
                            'idNumber' => $dirigeant->rc ?: $dirigeant->carte_identite,
                        ],
                    ];
                }
                return $dirigeant;
            })
            ->filter(function ($dirigeant) {
                $isModified = (int) data_get($dirigeant, 'is_modified', 0) === 1;
                $isDeleted = (int) data_get($dirigeant, 'is_deleted', 0) === 1;
                return $isModified || $isDeleted;
            })
            ->sortBy('created_at')
            ->values();
        $users = $shareholders->merge($dirigeants)->values();
        // return $users;
        if ($langue == 'fr' && $data) {
            if ($request->query('download')) {
                return view('pdfs.fr.formulaire2', ['data' => $bceData, 'download' => true, 'demande' => $demande,'requestUser'=>$requestUser,'users'=>$users, 'attestation' => $attestation]);
            } else {
                return view('pdfs.fr.formulaire2', ['data' => $bceData, 'demande' => $demande,'requestUser'=>$requestUser,'users'=>$users, 'attestation' => $attestation]);
            }
        }else if($langue == 'nl' && $data){
            if($request->query('download')){
                return view('pdfs.nl.formulier2',['data' => $bceData,'download'=>true, 'demande' => $demande,'requestUser'=>$requestUser,'users'=>$users, 'attestation' => $attestation]);
            }else{
                return view('pdfs.nl.formulier2',['data' => $bceData, 'demande' => $demande,'requestUser'=>$requestUser,'users'=>$users, 'attestation' => $attestation]);
            }
        } else {

        }
    }
    public function formulaire3(Request $request)
    {
        $demande_id = $request->query('demande_id');

        $data = Demande::with(['verification', 'shareholders.verification', 'dirigeants.verification', 'depositaire'])->find($demande_id);
        $langue = $data->langue_entreprise;
        $requestUser = null;
        $idAnalyzerService = new IdAnalyzerService();
        $depositaire = Depositaire::where('demande_id', $demande_id)->first();

        if ($data->verification && $data->verification->veriff_session) {
            $requestUser = $idAnalyzerService->getPersonData($data->verification->veriff_session)->getData(true);
            $requestUser = $this->buildVerifiedPersonPayload($idAnalyzerService, $data->verification->veriff_session, $requestUser);
        }
        // $shareholders = $data->shareholders
        //     ->filter(function ($shareholder) {
        //         return $shareholder->verification && $shareholder->verification->veriff_session;
        //     })
        //     ->map(function ($shareholder) {
        //         $veriffService = new VeriffService();
        //         $shareholder->data = $veriffService->getPersonData($shareholder->verification->veriff_session)->getData(true);
        //         return $shareholder;
        //     });
        $dirigeants = $data->dirigeants
            ->map(function ($dirigeant) {
                if ($dirigeant->verification && $dirigeant->verification->veriff_session) {
                    $idAnalyzerService = new IdAnalyzerService();
                    $sessionId = $dirigeant->verification->veriff_session;
                    $fallback = $idAnalyzerService->getPersonData($sessionId)->getData(true);
                    $dirigeant->data = $this->buildVerifiedPersonPayload($idAnalyzerService, $sessionId, $fallback);
                } else {
                    $dirigeant->data = [
                        'person' => [
                            'firstName' => $dirigeant->givenName,
                            'lastName' => $dirigeant->surname,
                            'idNumber' => $dirigeant->rc ?: $dirigeant->carte_identite,
                        ],
                    ];
                }
                return $dirigeant;
            })
            ->filter(function ($dirigeant) {
                return (int) data_get($dirigeant, 'is_modified', 0) === 1
                    && (int) data_get($dirigeant, 'is_deleted', 0) !== 1;
            })
            ->sortBy('created_at')
            ->values();

        if ($dirigeants->isEmpty()) {
            $firstName = $depositaire->prenom ?? data_get($requestUser, 'person.firstName', '');
            $lastName = $depositaire->nom ?? data_get($requestUser, 'person.lastName', '');
            $idNumber = $depositaire->numero_registre_national
                ?? data_get($requestUser, 'person.idNumber', '')
                ?? $depositaire->carte_identite
                ?? '';
            $function = $depositaire->fonction ?? 'administrateur';

            if ($firstName || $lastName || $idNumber) {
                $dirigeants = collect([
                    [
                        'data' => [
                            'person' => [
                                'firstName' => $firstName,
                                'lastName' => $lastName,
                                'idNumber' => $idNumber,
                            ],
                        ],
                        'function' => $function,
                    ],
                ]);
            }
        }

        if ($langue == 'fr' && $data) {
            if ($request->query('download')) {
                return view('pdfs.fr.declaration-particuliere', ['data' => json_decode($data->bce_data, true), 'download' => true,'demande' => $data,'requestUser'=>$requestUser,'dirigeants'=>$dirigeants]);
            } else {
                return view('pdfs.fr.declaration-particuliere', ['data' => json_decode($data->bce_data, true),'demande' => $data,'requestUser'=>$requestUser,'dirigeants'=>$dirigeants]);
            }
        }else if($langue == 'nl' && $data){
            if($request->query('download')){
                return view('pdfs.nl.bijzondere-verklaring-in-toepassing',['data' => json_decode($data->bce_data,true),'download'=>true,'demande' => $data,'requestUser'=>$requestUser,'dirigeants'=>$dirigeants]);
            }else{
                return view('pdfs.nl.bijzondere-verklaring-in-toepassing',['data' => json_decode($data->bce_data,true),'demande' => $data,'requestUser'=>$requestUser,'dirigeants'=>$dirigeants]);
            }
        } else {

        }
    }
    public function formulaire4(Request $request)
    {
        $demande_id = $request->query('demande_id');

        $data = Demande::with(['verification'])->find($demande_id);
        $langue = $data->langue_entreprise;
        $requestUser = null;
        $idAnalyzerService = new IdAnalyzerService();

        if ($data->verification && $data->verification->veriff_session) {
            $requestUser = $idAnalyzerService->getPersonData($data->verification->veriff_session)->getData(true);
            $requestUser = $this->buildVerifiedPersonPayload($idAnalyzerService, $data->verification->veriff_session, $requestUser);
        }

        if ($langue == 'fr' && $data) {
            if ($request->query('download')) {
                return view('pdfs.fr.attestation-didentite-modele-1', ['data' => json_decode($data->bce_data, true), 'download' => true, 'demande' => $data,'requestUser'=>$requestUser]);
            } else {
                return view('pdfs.fr.attestation-didentite-modele-1', ['data' => json_decode($data->bce_data, true), 'demande' => $data,'requestUser'=>$requestUser]);
            }
        }else if($langue == 'nl' && $data){
            if($request->query('download')){
                return view('pdfs.nl.bijzondere',['data' => json_decode($data->bce_data,true),'download'=>true, 'demande' => $data,'requestUser'=>$requestUser]);
            }else{
                return view('pdfs.nl.bijzondere',['data' => json_decode($data->bce_data,true), 'demande' => $data,'requestUser'=>$requestUser]);
            }
        } else {

        }
    }

    public function modele_a(Request $request)
    {
        $demande_id = $request->query('demande_id');

        $data = Demande::with(['verification', 'user', 'depositaire'])->find($demande_id);
        $demande = $data;
        $depositaire = null;
        if ($demande_id) {
            $depositaire = Depositaire::where('demande_id', $demande_id)->first();
        }
        // return json_decode($data->bce_data);
        $langue = $data->langue_entreprise;
        $requestUser = null;
        $idAnalyzerService = new IdAnalyzerService();

        if ($data->verification && $data->verification->veriff_session) {
            $requestUser = $idAnalyzerService->getPersonData($data->verification->veriff_session)->getData(true);
            $requestUser = $this->buildVerifiedPersonPayload($idAnalyzerService, $data->verification->veriff_session, $requestUser);
        }
        \Log::info('Attestation modele 1 data', [
            'demande_id' => $demande_id,
            'depositaire' => $depositaire,
            'verification_gsm' => $demande?->verification?->GSM,
            'user_email' => $demande?->user?->email,
            'request_user' => $requestUser,
        ]);
        if ($langue == 'fr' && $data) {
            if ($request->query('download')) {
                return view('pdfs.fr.attestation_identite_1', ['data' => json_decode($data->bce_data, true), 'download' => true, 'demande' => $demande, 'requestUser' => $requestUser, 'depositaire' => $depositaire]);
            } else {
                return view('pdfs.fr.attestation_identite_1', ['data' => json_decode($data->bce_data, true), 'demande' => $demande, 'requestUser' => $requestUser, 'depositaire' => $depositaire]);
            }
        }else if($langue == 'nl' && $data){
            if($request->query('download')){
                return view('pdfs.nl.attestation_identite_1',['data' => json_decode($data->bce_data,true),'download'=>true, 'demande' => $demande, 'requestUser' => $requestUser, 'depositaire' => $depositaire]);
            }else{
                return view('pdfs.nl.attestation_identite_1',['data' => json_decode($data->bce_data,true), 'demande' => $demande, 'requestUser' => $requestUser, 'depositaire' => $depositaire]);
            }
        } else {

        }
    }

    public function modele_b(Request $request)
    {
        $demande_id = $request->query('demande_id');
        $requestUser = null;
        $data = Demande::with(['verification', 'user'])->find($demande_id);
        $demande = $data;


        $userEmail = $demande?->user?->email;
        if($userEmail){
            $cabinet = DB::table('authorize_users')->where('email',$userEmail)->first()?->name ?? '' ;
        }
        $langue = $data->langue_entreprise;

        $idAnalyzerService = new IdAnalyzerService();

                    if($data->verification && $data->verification->veriff_session){
                            $requestUser = $idAnalyzerService->getPersonData($data->verification->veriff_session)->getData(true);
                            $requestUser = $this->buildVerifiedPersonPayload($idAnalyzerService, $data->verification->veriff_session, $requestUser);
                }

        if ($langue == 'fr' && $data) {
            if ($request->query('download')) {
                return view('pdfs.fr.attestation_identite_2', ['data' => json_decode($data->bce_data, true), 'download' => true, 'demande' => $demande,'requestUser'=>$requestUser,'cabinet'=>$cabinet]);
            } else {
                return view('pdfs.fr.attestation_identite_2', ['data' => json_decode($data->bce_data, true), 'demande' => $demande,'requestUser'=>$requestUser,'cabinet'=>$cabinet]);
            }
        }else if($langue == 'nl' && $data){
            if($request->query('download')){
                return view('pdfs.nl.attestation_identite_2',['data' => json_decode($data->bce_data,true),'download'=>true, 'demande' => $demande,'requestUser'=>$requestUser,'cabinet'=>$cabinet]);
            }else{
                return view('pdfs.nl.attestation_identite_2',['data' => json_decode($data->bce_data,true), 'demande' => $demande,'requestUser'=>$requestUser,'cabinet'=>$cabinet]);
            }
        } else {

        }
    }

    private function wordwrap_alnum($text, $limit = 80, $break = '<br>')
    {
        $text = str_replace(["\n"], "<br>", $text);
        // On divise par ligne (en gardant les \n)
        $lines = explode("<br>", $text);
        $wrappedText = '';

        foreach ($lines as $line) {
            // Traitement d'une ligne à la fois
            $words = preg_split('/(\s+)/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE); // conserve les espaces
            $currentLine = '';
            $currentLength = 0;

            foreach ($words as $chunk) {
                // Pour les espaces, on les ajoute sans compter leur longueur
                if (preg_match('/^\s+$/u', $chunk)) {
                    $currentLine .= $chunk;
                    continue;
                }

                // Longueur visible du mot (on enlève les balises HTML et on gère les entités)
                $visibleChunk = html_entity_decode(strip_tags($chunk), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $chunkLength = mb_strlen($visibleChunk, 'UTF-8');

                if ($currentLength + $chunkLength > $limit) {
                    // Saut de ligne si dépasser la limite
                    $wrappedText .= rtrim($currentLine) . $break;
                    $currentLine = $chunk;
                    $currentLength = $chunkLength;
                } else {
                    $currentLine .= $chunk;
                    $currentLength += $chunkLength;
                }
            }

            // Ajouter la dernière partie de la ligne
            $wrappedText .= $currentLine . $break;
        }

        // Supprimer le dernier <br> ajouté inutilement
        return rtrim($wrappedText, $break);
    }




    private function splitTextByLines(string $text, int $maxLines = 25): array
    {
        // Découper le texte en lignes
        $lines = preg_split("/\r\n|\r|\n|<br>/i", $text);

        // Séparer selon le nombre de lignes
        $part1 = implode("<br>", array_slice($lines, 0, $maxLines));
        $part2 = implode("<br>", array_slice($lines, $maxLines));
        if ($part2 === '') {
            // Force a non-empty value so page 3 renders even with no overflow text.
            $part2 = ' ';
        }

        return [
            'part1' => $part1,
            'part2' => $part2,
        ];
    }

     public function pvAg(Request $request) {
        $demande_id = $request->query('demande_id');
        $demande = Demande::find($demande_id);
       
        $jsonData = json_decode($demande->bce_data, true);
        $denomination = $jsonData['denomination']['description']['value'] ?? $jsonData['denomination'][0]['description']['value'] ?? '';
        $form_juridique = $jsonData['juridicalForm']['description']['value'] ?? $jsonData['juridicalForm'][0]['description']['value'] ?? '';
        $address = $jsonData['address']?? null;
       if (!empty($address)) {
        $houseNumber = $address['houseNumber'] ?? '';
        $street      = $address['street']['description']['value'] ?? '';
        $zipcode     = $address['zipcode'] ?? '';
        $municipality= $address['municipality']['description']['value'] ?? '';
        $country     = $address['country'] ?? '';
       }

       // Normalize 'function' into an indexed array. BCE sometimes returns a single associative object.
       $dirs = [];
       if (isset($jsonData['function'])) {
           $f = $jsonData['function'];
           if (!is_array($f)) {
               $dirs = [$f];
           } else {
               // detect if associative (single object) vs indexed list
               $isAssoc = array_keys($f) !== range(0, count($f) - 1);
               $dirs = $isAssoc ? [$f] : $f;
           }
       }

       Log::info('Dirs Array: ' . print_r($dirs, true));

       $dirigeants = collect($dirs)->map(function ($d) {
                // If the entry is a JSON string, try decode
                if (is_string($d)) {
                    $decoded = json_decode($d, true);
                    if (is_array($decoded)) {
                        $d = $decoded;
                    }
                }

                $surname = null;
                $givenName = null;

                $person = $d['person'] ?? null;
                if (is_array($person)) {
                    $surname = $person['surname'] ?? $person['familyName'] ?? null;
                    $givenName = $person['givenName'] ?? $person['firstName'] ?? null;
                    // $depositaire = Depositaire::where('demande_id', $demande_id)->first();
                } elseif (is_string($person)) {
                    $parts = preg_split('/\s+/', trim($person));
                    $surname = array_pop($parts) ?: null;
                    $givenName = count($parts) ? implode(' ', $parts) : null;
                } else {
                    // fallback to top-level keys if present
                    $surname = $d['surname'] ?? null;
                    $givenName = $d['givenName'] ?? null;
                }

                return [
                    'surname'   => $surname,
                    'givenName' => $givenName,
                ];
       });

        return view('pdfs.pv_ag', ['demande' => $demande, 'denomination' => $denomination, 'form_juridique' => $form_juridique, 'dirigeants' => $dirigeants, 'street' => $street, 'houseNumber' => $houseNumber, 'zipcode' => $zipcode, 'municipality' => $municipality, 'country' => $country, 'download' => $request->download]);

    }

    public function paymentConditions(Request $request) {
        $demande_id = $request->query('demande_id');
        $data = Demande::with(['verification', 'user'])->find($demande_id);
        $langue = $data->langue_entreprise;

        if ($langue == 'fr' && $data) {
            if ($request->query('download')) {
                return view('pdfs.fr.payment_conditions', [ 'download' => true]);
            } else {
                return view('pdfs.fr.payment_conditions');
            }
        }else if($langue == 'nl' && $data){
            if($request->query('download')){
                return view('pdfs.nl.payment_conditions',['download'=>true]);
            }else{
                return view('pdfs.nl.payment_conditions');
            }
        } else {

        }
    }

}
