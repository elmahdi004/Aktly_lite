<?php


namespace App\Http\Controllers;

use App\Models\Demande;
use App\Http\Requests\StoreDemandeRequest;
use App\Http\Requests\UpdateDemandeRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Verification;
use App\Services\TwilioService;
use App\Services\IdAnalyzerService;
use App\Services\ShortLinkService;
use App\Services\OpenAIService;
use App\Services\PdfFieldsService;
use App\Services\StripeUsageService;
use App\Services\CouponValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemandeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $twilioService;
    protected $idAnalyzerService;
    protected $shortLinkService;
    protected $usageService;
    protected $couponService;

    public function __construct(
        IdAnalyzerService $idAnalyzerService,
        TwilioService $twilioService,
        ShortLinkService $shortLinkService,
        StripeUsageService $usageService,
        CouponValidationService $couponService
    ) {
        $this->twilioService = $twilioService;
        $this->idAnalyzerService = $idAnalyzerService;
        $this->shortLinkService = $shortLinkService;
        $this->usageService = $usageService;
        $this->couponService = $couponService;
    }
    public function index()
    {
        return Demande::with('user')->get();
    }

    public function getWithSharesHolders($id)
    {
        return Demande::with(['shareholders.verification'=>function($query){$query->select('id','GSM');}])->findOrFail($id);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDemandeRequest $request)
    {
        $user = Auth::user();
        $demande = null;
        $exceededQuota = false;
        $appliedCoupon = null;
        $usedSubscription = false;
        $dossierCreditCost = 1;

        try {
            $result = DB::transaction(function () use ($request, $user, &$exceededQuota, &$appliedCoupon, &$usedSubscription, $dossierCreditCost) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                // Access can come from active subscription, valid coupon, or available one-time credits.
                $hasValidSubscription = $lockedUser && $lockedUser->isSubscriptionActive();
                $hasValidCoupon = $lockedUser && $this->couponService->isUserCouponValid($lockedUser);
                $hasSufficientCredits = $lockedUser && (($lockedUser->credits ?? 0) >= $dossierCreditCost);

                if (!$hasValidSubscription && !$hasValidCoupon && !$hasSufficientCredits) {
                    return ['error' => 'insufficient_access'];
                }

                // If coupon expired during check, refresh user
                if (!$hasValidCoupon && $lockedUser->applied_coupon_code) {
                    $lockedUser = $lockedUser->fresh();
                }

                // Store coupon code before creating dossier
                $appliedCoupon = $lockedUser->applied_coupon_code;
                
                // If using coupon, check dossier limits
                if ($hasValidCoupon && $appliedCoupon) {
                    $coupon = \App\Models\Coupon::where('code', $appliedCoupon)->first();
                    if ($coupon) {
                        // Get current usage by coupon_id
                        $usage = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                            ->where('user_id', $lockedUser->id)
                            ->first();
                        
                        $currentUsed = $usage ? $usage->dossiers_used : 0;
                        
                        // Check if user would exceed coupon limit
                        if ($currentUsed >= $coupon->max_dossiers) {
                            return ['error' => 'coupon_limit_exceeded'];
                        }
                    }
                }

                // If user is using one-time credits (no subscription/coupon), consume credits now.
                if (!$hasValidSubscription && !$hasValidCoupon) {
                    $lockedUser->decrement('credits', $dossierCreditCost);
                }

                $previousCount = $lockedUser->cumulative_dossier_count;
                $lockedUser->increment('cumulative_dossier_count');
                $newCount = $previousCount + 1;
                
                // Get quota based on active subscription or coupon
                $quota = $hasValidSubscription 
                    ? $lockedUser->getPlanQuota()
                    : PHP_INT_MAX; // Unlimited for coupon-based

                $exceededQuota = $newCount > $quota;
                $usedSubscription = $hasValidSubscription;

                $demande = Demande::create([
                    'user_id' => $lockedUser->id,
                    'bce_data' => json_encode($request->bce_data),
                    'enterprise_number' => $request->enterprise_number,
                    'langue_entreprise' => $request->langue
                        ?? ($request->bce_data['lang_entre'] ?? 'fr'),
                ]);

                return ['demande' => $demande, 'user' => $lockedUser->fresh()];
            });

            // Check for errors
            if (isset($result['error'])) {
                if ($result['error'] === 'coupon_limit_exceeded') {
                    return response()->json([
                        'message' => 'You have reached your dossier limit with this coupon. Please apply a new coupon or subscribe.',
                    ], 402);
                }
                
                return response()->json([
                    'message' => 'You need an active subscription, a valid coupon, or at least 1 credit to create a dossier.',
                ], 402);
            }

            $demande = $result['demande'];
            $user = $result['user'];

            // Record dossier usage for coupon (regardless of quota status)
            if ($appliedCoupon) {
                $this->couponService->recordDossierUsage(
                    $appliedCoupon,
                    $user->id,
                    1
                );
            }

            // Report extra dossier usage for subscription when quota exceeded
            if ($exceededQuota && $usedSubscription && !$appliedCoupon) {
                $this->usageService->reportExtraDossier($user, 1);
            }

            return response()->json([
                'demande_id' => $demande->id,
                'demande' => $demande,
                'dossiers_remaining' => $user->getDossiersRemaining(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Dossier creation failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création de la demande.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Demande $demande)
    {
        $demande = $demande->load([
            'dirigeants.verification' => function($query) {
                $query->select('id', 'GSM', 'veriff_session');
            },
            'shareholders.verification' => function($query) {
                $query->select('id', 'GSM', 'veriff_session');
            }
        ]);
        return response()->json(['demande' => $demande]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Demande $demande)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDemandeRequest $request, Demande $demande)
    {
        // $demande = Demande::find($demande);
        // if(!$demande){
        //     return response()->json(['message'=>'the demande does not existe'],401);
        // }
        $demande = Demande::where('id',$demande->id)->update($request->all());
        return response()->json(['demande'=>$demande]); 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Demande $demande)
    {
        //
        if (Auth::id() !== $demande->user_id && !Auth::user()->is_admin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $demande->delete();

        return response()->json(['message' => 'Demande deleted successfully']);
    }
    public function updateGSM(UpdateDemandeRequest $request,Demande $demande){
        // Validate international phone number format (E.164)
        $phoneNumber = $request->GSM;
        
        Demande::where('id',$demande->id)->update($request->all());
        $session = $this->idAnalyzerService->createSession();
        if (!is_array($session) || empty($session['session_id'])) {
            $errorMessage = is_array($session) && !empty($session['error'])
                ? $session['error']
                : 'Failed to create verification session';
            return response()->json(['error' => $errorMessage], 500);
        }
        if($session['session_id']){
            $link = $this->shortLinkService->store(link: $session['verification_url'])->getData()->short_url;
            $d = Demande::where('id',$demande->id)->update(['veriff_session'=>$session['session_id'],'veriff_url'=>$link]);
            if($d){
                $to = $request->GSM;
                $message = "Cliquez ici pour verifier votre identite via ID Analyzer : $link";
                $this->twilioService->sendSms($to,$message);
            }
        }else{
            return response()->json(['error'=>"Something want wrong !"]);
        }
    }

    public function userDemandes(){
        $demandes = Demande::where('user_id',Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($demandes);
    }
    public function lastDemandes(){
        $demandes = Demande::with(['user'])->orderBy('created_at','desc')->take(5)->get();
        return response()->json($demandes);
    }
    public function count(){
        $demandes = Demande::count();
        $tickets = Ticket::count();
        $users = User::count();
        return response()->json(['demandes'=>$demandes,'tickets'=>$tickets,'users'=>$users]);
    }
    public function countClient(){
        $demandes = Demande::where('user_id',Auth::user()->id)->count();
        $tickets = Ticket::whereHas('demande', function ($query) {
            $query->where('user_id', Auth::id());
        })->with('demande.user')->count();;
        return response()->json(['demandes'=>$demandes,'tickets'=>$tickets]);
    }
    public function getMyDemandes(){
        return Demande::with('user')
            ->where('user_id', Auth::user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function changeState(Request $request, $demande_id)
    {
        $state = $request['state']; // or $request->state
    
        // Example: update the Demande model
        $demande = Demande::findOrFail($demande_id)->update([
            'status' => $state == true ? 'resolved' : 'cancelled'
        ]);
        // $demande->state = $state;
        // $demande->save();
    
        return response()->json([
            'message' => 'State updated successfully',
            'data' => $demande
        ]);
    }

    public function storeText(Request $request, $demande_id)
    {
        $text = $request->text;
        //afficher texte dans log
        Log::info('Text to store: ' . $text);
        $demande = Demande::findOrFail($demande_id);
        $text = app(OpenAIService::class)->sanitizePvText($text);
        $missingFields = app(OpenAIService::class)->getMissingFields($demande);
        if (!empty($missingFields)) {
            return response()->json(['missing_fields' => $missingFields], 422);
        }
        if($text!= null && $text != '') {
            $demande->pub_text = $text;
            $demande->save();
        } else {
            return response()->json(['error' => 'Text is required'], 422);
        }
        return response()->json([
            'message' => 'Text stored successfully',
        ]);
    }

    public function missingPdfFields(Request $request, Demande $demande)
    {
        $form = $request->query('form');
        $demande->loadMissing('address');
        $missingFields = app(PdfFieldsService::class)->getMissingFields($demande, $form);

        return response()->json([
            'missing_fields' => $missingFields,
        ]);
    }

    public function pdfFields(Request $request, Demande $demande)
    {
        $form = $request->query('form');
        $demande->loadMissing('address');
        $fields = app(PdfFieldsService::class)->getFormFields($demande, $form);

        return response()->json([
            'fields' => $fields,
        ]);
    }

    public function updatePdfFields(Request $request, Demande $demande)
    {
        if (!Schema::hasColumn('demandes', 'pdf_fields')) {
            Log::error('Missing pdf_fields column on demandes table', [
                'demande_id' => $demande->id,
            ]);

            return response()->json([
                'message' => 'La colonne pdf_fields est manquante. Veuillez exécuter les migrations.',
            ], 500);
        }

        $payload = $request->input('fields', $request->all());
        if (!is_array($payload)) {
            $payload = [];
        }

        try {
            $updated = app(PdfFieldsService::class)->updatePdfFields($demande, $payload);

            return response()->json([
                'message' => 'PDF fields updated successfully',
                'demande' => $updated,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to update pdf_fields', [
                'demande_id' => $demande->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour des champs PDF.',
            ], 500);
        }
    }

    public function getVerificationData($demande_id)
    {
        $demande = Demande::with([
            'depositaire',
            'verification' => function($query) {
                $query->select('id', 'GSM', 'veriff_session', 'veriff_url');
            },
            'shareholders.verification' => function($query) {
                $query->select('id', 'GSM', 'veriff_session', 'veriff_url');
            },
            'dirigeants.verification' => function($query) {
                $query->select('id', 'GSM', 'veriff_session', 'veriff_url');
            },
        ])->find($demande_id);
        if (!$demande) {
            return response()->json(['error' => 'Demande not found'], 404);
        }

        $verificationData = [];
        $veriffService = new \App\Services\VeriffService();

        $extractDetails = function (array $decision) {
            $verification = $decision['verification'] ?? $decision;
            $person = $verification['person']
                ?? ($verification['result']['person'] ?? null)
                ?? ($verification['identity']['person'] ?? null)
                ?? ($verification['data']['person'] ?? null)
                ?? [];
            $document = $verification['document']
                ?? ($verification['documentInfo'] ?? null)
                ?? ($verification['identity']['document'] ?? null)
                ?? ($verification['data']['document'] ?? null)
                ?? [];
            $address = $verification['address']
                ?? ($person['address'] ?? null)
                ?? ($document['address'] ?? null)
                ?? ($verification['result']['address'] ?? null)
                ?? [];
            $finalTransaction = $decision['finalTransaction']
                ?? ($verification['finalTransaction'] ?? null);
            $data = $decision['data'] ?? ($verification['data'] ?? null) ?? [];
            if (empty($data) && !empty($finalTransaction['data'])) {
                $data = $finalTransaction['data'];
            }

            $normalize = function ($value) {
                if (is_array($value)) {
                    $value = array_filter($value, fn($item) => $item !== null && $item !== '');
                    return trim(implode(' ', $value));
                }
                if (is_scalar($value)) {
                    return trim((string) $value);
                }
                return '';
            };

            $getDataValue = function (string $key) use ($data, $normalize) {
                if (!is_array($data) || !array_key_exists($key, $data)) {
                    return '';
                }
                $entry = $data[$key];
                if (is_array($entry)) {
                    $first = $entry[0]['value'] ?? $entry[0] ?? null;
                    return $normalize($first);
                }
                return $normalize($entry);
            };

            $buildAddress = function ($addressValue) use ($normalize) {
                if (is_string($addressValue)) {
                    return trim($addressValue);
                }
                if (!is_array($addressValue)) {
                    return '';
                }
                $parts = array_filter([
                    $addressValue['line1'] ?? $addressValue['address1'] ?? $addressValue['address'] ?? '',
                    $addressValue['line2'] ?? $addressValue['address2'] ?? '',
                    $addressValue['postcode'] ?? $addressValue['zip'] ?? $addressValue['postalCode'] ?? '',
                    $addressValue['city'] ?? $addressValue['town'] ?? '',
                    $addressValue['state'] ?? $addressValue['region'] ?? '',
                    $addressValue['country'] ?? '',
                ], fn($item) => $item !== null && $item !== '');
                return trim(implode(', ', $parts));
            };

            $firstName = $normalize($person['firstName'] ?? $person['givenName'] ?? $person['given_name'] ?? '');
            $lastName = $normalize($person['lastName'] ?? $person['surname'] ?? $person['familyName'] ?? $person['family_name'] ?? '');
            $fullName = $normalize($person['fullName'] ?? $person['name'] ?? trim("{$firstName} {$lastName}"));

            if ($firstName === '') {
                $firstName = $getDataValue('firstName');
            }
            if ($lastName === '') {
                $lastName = $getDataValue('lastName');
            }
            if ($fullName === '') {
                $fullName = $getDataValue('fullName');
            }

            $details = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'fullName' => $fullName,
                'birthDate' => $normalize($person['dob'] ?? $person['birthDate'] ?? $person['dateOfBirth'] ?? $document['birthDate'] ?? $document['dob'] ?? '')
                    ?: $getDataValue('dob'),
                'birthPlace' => $normalize($person['birthPlace'] ?? $document['birthPlace'] ?? '')
                    ?: $getDataValue('birthPlace'),
                'nationality' => $normalize($person['nationality'] ?? $document['nationality'] ?? '')
                    ?: $getDataValue('nationalityFull')
                    ?: $getDataValue('nationality'),
                'gender' => $normalize($person['gender'] ?? $document['gender'] ?? '')
                    ?: $getDataValue('sex'),
                'documentNumber' => $normalize($document['documentNumber'] ?? $document['number'] ?? $document['idNumber'] ?? $person['idNumber'] ?? '')
                    ?: $getDataValue('documentNumber')
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
        };

        $fetchMedia = function ($sessionId) use ($veriffService, $extractDetails) {
            if (empty($sessionId)) {
                return ['status' => 'pending', 'error' => 'No verification session found'];
            }

            try {
                \Log::info('Fetching Veriff decision for session', ['session_id' => $sessionId]);

                // getDecision() gracefully degrades to session status (and null verification)
                // when the holder hasn't finished yet — so a "pending" result is normal here.
                $decisionResponse = $veriffService->getDecision($sessionId);
                $decision = json_decode($decisionResponse->getContent(), true);
                $decision = is_array($decision) ? $decision : [];

                $verification = $decision['verification'] ?? null;
                if (empty($verification)) {
                    return ['status' => 'pending'];
                }

                $status = $verification['status'] ?? ($decision['status'] ?? 'submitted');
                $details = $extractDetails($decision);

                // Veriff media (ID front/back + face) must be fetched through our HMAC-signed
                // proxy — the raw media URLs require X-AUTH-CLIENT/X-HMAC-SIGNATURE headers.
                $images = [];
                try {
                    $mediaResponse = $veriffService->getPersonMedia($sessionId);
                    $mediaData = json_decode($mediaResponse->getContent(), true);
                    $mediaList = [];
                    if (is_array($mediaData)) {
                        $mediaList = $mediaData['images'] ?? $mediaData['videos'] ?? $mediaData ?? [];
                    }
                    if (is_array($mediaList)) {
                        foreach ($mediaList as $m) {
                            if (!is_array($m)) {
                                continue;
                            }
                            $mid = $m['id'] ?? null;
                            if (!$mid) {
                                continue;
                            }
                            $context = $m['context'] ?? $m['name'] ?? 'document';
                            $images[] = [
                                'id' => $mid,
                                'name' => $context,
                                'url' => url('/api/veriff/media/' . $mid),
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Veriff media fetch failed', [
                        'session_id' => $sessionId,
                        'error' => $e->getMessage(),
                    ]);
                }

                \Log::info('Veriff decision processed', [
                    'session_id' => $sessionId,
                    'status' => $status,
                    'image_count' => count($images),
                    'has_details' => !empty($details),
                ]);

                return array_filter([
                    'status' => $status,
                    'details' => $details,
                    'images' => $images,
                ], fn($v) => $v !== null && $v !== [] && $v !== '');
            } catch (\Throwable $e) {
                \Log::error('Veriff getVerificationData fetch failed', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
                return ['status' => 'pending', 'error' => 'Failed to fetch verification data'];
            }
        };

        // Get user verification data (prefer demande.veriff_session when set)
        $userPhone = null;
        $userSessionId = null;
        if ($demande->verification) {
            $userPhone = $demande->verification->GSM ?? $demande->GSM ?? null;
            $userSessionId = $demande->verification->veriff_session ?? null;
        } else {
            $userPhone = $demande->GSM ?? null;
            $userSessionId = $demande->veriff_session ?? null;
        }

        if (Schema::hasColumn('demandes', 'veriff_session') && !empty($demande->veriff_session)) {
            if ($userSessionId !== $demande->veriff_session) {
                $userSessionId = $demande->veriff_session;
                if ($demande->verification && $demande->verification->veriff_session !== $userSessionId) {
                    $demande->verification->update([
                        'veriff_session' => $userSessionId,
                    ]);
                }
            }
        }

        if (!$userSessionId && $userPhone) {
            $latestVerification = Verification::where('GSM', $userPhone)
                ->orderByDesc('id')
                ->first();
            if ($latestVerification) {
                $userSessionId = $latestVerification->veriff_session;
                if (!$demande->verification_id) {
                    $demande->verification_id = $latestVerification->id;
                }
                if (Schema::hasColumn('demandes', 'veriff_session')) {
                    $demande->veriff_session = $userSessionId;
                }
                if ($demande->isDirty()) {
                    $demande->save();
                }
            }
        }

        if (!$userSessionId && $demande->user) {
            $userSessionId = $demande->user->veriff_session ?? null;
        }

        if ($userPhone && $userSessionId && !$demande->verification_id) {
            $verification = Verification::where('veriff_session', $userSessionId)
                ->orWhere('GSM', $userPhone)
                ->first();

            if ($verification) {
                if ($verification->veriff_session !== $userSessionId) {
                    $verification->update([
                        'veriff_session' => $userSessionId,
                    ]);
                }
            } else {
                $verification = Verification::create([
                    'GSM' => $userPhone,
                    'veriff_session' => $userSessionId,
                    'veriff_url' => null,
                ]);
            }

            $demande->verification_id = $verification->id;
            if (Schema::hasColumn('demandes', 'veriff_session')) {
                $demande->veriff_session = $userSessionId;
            }
            if (Schema::hasColumn('demandes', 'GSM')) {
                $demande->GSM = $userPhone;
            }
            if ($demande->isDirty()) {
                $demande->save();
            }
        }

        // Aktly Lite: the dossier administrator (dépositaire) is the single dirigeant the user
        // selected and whose identity was verified via Veriff. The verification session lives on
        // the demande itself (veriff_session / verification). Note: `depositaire` is a string
        // column here ("gestionnaire"), so we resolve the person from the linked dirigeant — not
        // from $demande->depositaire (which would return the column value, shadowing the relation).
        $selectedName = null;
        $selectedCard = null;
        foreach ($demande->dirigeants as $dirigeant) {
            $isSelected = $demande->verification_id
                && $dirigeant->verification_id === $demande->verification_id;
            if ($isSelected || $selectedName === null) {
                $selectedName = trim(($dirigeant->givenName ?? '') . ' ' . ($dirigeant->surname ?? ''));
                $selectedCard = $dirigeant->carte_identite ?? null;
            }
            if ($isSelected) {
                break;
            }
        }

        $adminResult = $userSessionId ? $fetchMedia($userSessionId) : ['status' => 'pending'];

        $verificationData['depositaire'] = array_merge([
            'name' => $selectedName ?: 'Dépositaire',
            'session_id' => $userSessionId,
            'phone' => $userPhone,
            'carte_identite' => $selectedCard,
        ], $adminResult);

        // Lite verifies exactly one person — no separate shareholders/dirigeants lists.
        $verificationData['shareholders'] = [];
        $verificationData['dirigeants'] = [];

        return response()->json($verificationData);
    }

    public function syncVerificationSession(Request $request, $demande_id)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $demande = Demande::find($demande_id);
        if (!$demande) {
            return response()->json(['error' => 'Demande not found'], 404);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user->is_admin && $demande->user_id !== $user->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $sessionId = trim($request->input('session_id'));
        $verification = Verification::where('veriff_session', $sessionId)->first();

        if (!$verification) {
            if ($demande->verification_id) {
                $verification = Verification::find($demande->verification_id);
            }
        }

        if (!$verification && Schema::hasColumn('demandes', 'GSM') && !empty($demande->GSM)) {
            $verification = Verification::where('GSM', $demande->GSM)->first();
        }

        if ($verification && $verification->veriff_session !== $sessionId) {
            $verification->update([
                'veriff_session' => $sessionId,
            ]);
        }

        $payload = [];
        if ($verification && $demande->verification_id !== $verification->id) {
            $payload['verification_id'] = $verification->id;
        }

        if (Schema::hasColumn('demandes', 'veriff_session')) {
            $payload['veriff_session'] = $sessionId;
        }

        if (!empty($payload)) {
            $demande->update($payload);
        }

        Log::info('Verification session synced', [
            'demande_id' => $demande_id,
            'session_id' => $sessionId,
            'verification_id' => $verification->id ?? null,
            'updated' => !empty($payload),
        ]);

        return response()->json([
            'message' => 'Verification session synced',
            'demande_id' => $demande_id,
            'session_id' => $sessionId,
            'verification_id' => $verification->id ?? null,
        ]);
    }

    public function getMediaContent($media_id, Request $request)
    {
        try {
            Log::info('[getMediaContent] start', ['media_id' => $media_id, 'download' => $request->boolean('download')]);

            $idAnalyzerService = app(\App\Services\IdAnalyzerService::class);
            Log::info('[getMediaContent] calling IdAnalyzerService#getMediaContent');
            $mediaResponse = $idAnalyzerService->getMediaContent($media_id); // already a Response with binary body
            Log::info('[getMediaContent] IdAnalyzerService response received', [
                'headers' => $mediaResponse->headers->all(),
                'status' => method_exists($mediaResponse, 'getStatusCode') ? $mediaResponse->getStatusCode() : 200,
            ]);

            $contentType = $mediaResponse->headers->get('Content-Type') ?: 'image/jpeg';
            
            // Ensure content type is valid
            if (!str_contains($contentType, 'image')) {
                // Try to detect from media_id or default to jpeg
                $contentType = 'image/jpeg';
            }
            
            Log::info('[getMediaContent] content type resolved', [
                'content_type' => $contentType,
                'is_image' => str_contains($contentType, 'image')
            ]);

            $binary = $mediaResponse->getContent();
            if ($binary !== '') {
                // Some upstream responses include a leading newline before JPEG bytes.
                $binary = ltrim($binary, "\r\n\t ");
            }
            $length = strlen($binary);
            Log::info('[getMediaContent] binary extracted', ['length' => $length]);

            // Guard: empty body
            if ($length === 0) {
                Log::warning('[getMediaContent] empty body returned from IdAnalyzerService', ['media_id' => $media_id]);
                return response()->json(['error' => 'Empty response from API'], 500);
            }

            // Verify magic bytes to ensure it's actually an image
            $isValidImage = false;
            if ($length >= 2) {
                $header = substr($binary, 0, 4);
                // Check JPEG (FF D8 FF)
                if (strpos($header, "\xFF\xD8\xFF") === 0) {
                    $isValidImage = true;
                    Log::info('[getMediaContent] JPEG magic bytes detected');
                }
                // Check PNG (89 50 4E 47)
                elseif (strpos($header, "\x89PNG") === 0) {
                    $isValidImage = true;
                    Log::info('[getMediaContent] PNG magic bytes detected');
                }
                // Check GIF (47 49 46)
                elseif (strpos($header, "GIF") === 0) {
                    $isValidImage = true;
                    Log::info('[getMediaContent] GIF magic bytes detected');
                }
            }
            
            if (!$isValidImage) {
                Log::warning('[getMediaContent] No valid image magic bytes detected', [
                    'first_bytes' => bin2hex(substr($binary, 0, 8)),
                    'length' => $length
                ]);
                // Check if it looks like an error response
                if (strpos($binary, '<') === 0 || strpos(ltrim($binary), '<') === 0) {
                    Log::error('[getMediaContent] Response looks like HTML error', [
                        'first_chars' => substr($binary, 0, 100)
                    ]);
                    return response()->json(['error' => 'API returned HTML error'], 400);
                }
            }

            // TEST: Save image locally to verify content
            $ext = 'jpg';
            if (str_contains($contentType, 'png')) { $ext = 'png'; }
            elseif (str_contains($contentType, 'gif')) { $ext = 'gif'; }
            elseif (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) { $ext = 'jpg'; }
            
            $testPath = storage_path('app/public/test_id_analyzer_' . $media_id . '.' . $ext);
            $writeBytes = file_put_contents($testPath, $binary);
            Log::info('[getMediaContent] TEST: Image saved locally', [
                'path' => $testPath,
                'bytes_written' => $writeBytes,
                'file_size' => filesize($testPath) ?? 0
            ]);

            if ($request->boolean('download')) {
                Log::info('[getMediaContent] download requested, returning binary');
                $ext = 'jpg';
                if (str_contains($contentType, 'png')) { $ext = 'png'; }
                elseif (str_contains($contentType, 'gif')) { $ext = 'gif'; }
                elseif (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) { $ext = 'jpg'; }
                
                $filename = 'id_analyzer_image_' . $media_id . '.' . $ext;
                
                // Return raw binary response - cleanest possible approach
                return response($binary)
                    ->header('Content-Type', $contentType)
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->header('Content-Length', strlen($binary));
            }
            
            // If not download parameter, return 404 to avoid confusion
            return response()->json(['error' => 'Missing download parameter'], 400);
        } catch (\Throwable $e) {
            Log::error('[getMediaContent] error', [
                'media_id' => $media_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to fetch media content'], 500);
        }
    }

    public function updatenbShares(Request $request, $id)
    {
        if(!$request->has('nb_total_actions') && empty($request->nb_total_actions)) {
            return response()->json(['error' => 'The nb_total_actions field is required.'], 400);
        }
        $demande = Demande::findOrFail($id);
        $demande->nb_total_actions = $request->nb_total_actions;
        $demande->save();

        return response()->json([
            'message' => 'Le nombre total d’actions a été mis à jour avec succès.',
            'nb_total_actions' => $demande->nb_total_actions,
        ]);
    }

    public function findGSM($gsm){
        $verification = Verification::where('GSM',$gsm)->first();
        if($verification){
            return response()->json(['exists'=>true,'verification'=>$verification]);
        }else{
            return response()->json(['exists'=>false]);
        }

    }


    public function updateProgress(Request $request, $demande_id)
    {
        $request->validate([
            'progress' => 'required|numeric|min:0|max:100',
        ]);
        $demande = Demande::findOrFail($demande_id);
        // Optionally, check if the demande belongs to the authenticated user
        if ($demande->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $demande->progress = $request->progress;
        $demande->save();
        return response()->json(['success' => true, 'progress' => $demande->progress]);
    }

     public function storeExtraitText(Request $request, $demande_id) {

        $text = $request->text;
        //afficher texte dans log
        Log::info('Text to store: ' . $text);
        $demande = Demande::findOrFail($demande_id);
        $text = app(OpenAIService::class)->sanitizeDocumentText($text);
        $missingFields = app(OpenAIService::class)->getMissingFields($demande);
        if (!empty($missingFields)) {
            return response()->json(['missing_fields' => $missingFields], 422);
        }
        if($text!= null && $text != '') {
            $demande->extrait_pub_text = $text;
            $demande->progress = 100;
            $demande->status = $demande->status ?: 'resolved';
            $demande->save();
        } else {
            return response()->json(['error' => 'Text is required'], 422);
        }
        return response()->json([
            'message' => 'Text stored successfully',
        ]);
    }

    /**
     * Get ID Analyzer authentication signature for frontend
     * This keeps API keys secure on backend while allowing frontend to fetch directly
     */
    public function getIdAnalyzerAuth($media_id)
    {
        try {
            $apiKey = env('ID_ANALYZER_API_KEY');
            $secretKey = env('ID_ANALYZER_SECRET_KEY');
            
            if (!$apiKey || !$secretKey) {
                return response()->json(['error' => 'ID Analyzer credentials not configured'], 500);
            }
            
            // Calculate HMAC signature
            $hmacSignature = hash_hmac('sha256', $media_id, $secretKey);
            
            return response()->json([
                'api_key' => $apiKey,
                'hmac_signature' => $hmacSignature,
                'media_id' => $media_id
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating ID Analyzer auth', [
                'media_id' => $media_id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to generate auth'], 500);
        }
    }
}
