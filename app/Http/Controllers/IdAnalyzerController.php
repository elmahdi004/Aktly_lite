<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Services\ShortLinkService;
use App\Services\TwilioService;
use App\Services\IdAnalyzerService;
use App\Models\Demande;
use App\Models\DirigeantsService;
use App\Models\ShareHolder;
use App\Models\Verification;
use Illuminate\Support\Facades\Schema;

class IdAnalyzerController extends Controller
{
    private $shortLinkService;
    private $twilioService;

    public function __construct(ShortLinkService $shortLinkService, TwilioService $twilioService) {
        $this->shortLinkService = $shortLinkService;
        $this->twilioService = $twilioService;
    }

    public function upload(){
        return view('idanalyzer.uploadPage');
    }
    public function createSession()
    {
        $kboData = Session::get('api-data');
        if(!$kboData){
            return redirect()->route('idanalyzer.error_page');
        }
        $profileId = (string) config('services.id_analyzer.profile_id');
        if (empty($profileId)) {
            return redirect()->route('idanalyzer.error_page')
                ->with('error', 'ID Analyzer profile is not configured.');
        }
        try {
            $firstName = $kboData['function'][0]['person']['surname'] ?? '';
            $lastName = $kboData['function'][0]['person']['givenName'] ?? '';
            $payload = [
                'profile' => $profileId,
                'verifyName' => trim("{$firstName} {$lastName}"),
            ];

            $idAnalyzerService = app(IdAnalyzerService::class);
            $session = $idAnalyzerService->createSession($payload);

            $sessionId = $session['session_id'] ?? null;
            $verificationUrl = $session['verification_url'] ?? null;
            Session::put('veriff_active_session',$sessionId);
            return redirect()->away($verificationUrl);
        } catch (\Exception $e) {
            return response()->json(['error' => 'ID Analyzer API error: ' . $e->getMessage()], 500);
        }
    }

    public function getSessionStatus($sessionId)
    {
        $idAnalyzerService = app(IdAnalyzerService::class);
        return $idAnalyzerService->getSessionStatus($sessionId);
    }
    public function getPersonData($sessionId){
        $idAnalyzerService = app(IdAnalyzerService::class);
        return $idAnalyzerService->getPersonData($sessionId);
    }
    public function handleCallback()
    {
        $sessionId =  Session::get('veriff_active_session');
        if($sessionId){
            $decision = json_decode($this->getDecision($sessionId)->getContent());
            if($decision->verification->status === 'approved'){
                $kboData = Session::get('api-data');
                if(strtolower($kboData['function'][0]['person']['givenName']) == strtolower($decision->verification->person->firstName) && strtolower($kboData['function'][0]['person']['surname']) == strtolower($decision->verification->person->lastName)){
                    Session::put('veriff_active_person',$decision);
                    return redirect()->route('pdfs');
                }else{
                    return redirect()->route('idanalyzer.error_page')->with("error" , "We have an error please try later");
                }
            }
        return redirect()->route('idanalyzer.error_page')->with('error' , "Oops! It looks like we couldn't verify your ID. This might be due to a blurry photo, incorrect details, or an unsupported ID type. No worries—please double-check your document and try again. If you need help, feel free to reach out to our support team!");
        }
        return redirect()->route('idanalyzer.error_page')->with("error" , "We have an error can you try later");
    }
    public function getDecision($sessionId)
    {
        $idAnalyzerService = app(IdAnalyzerService::class);
        return $idAnalyzerService->getDecision($sessionId);
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
        $idAnalyzerService = app(IdAnalyzerService::class);
        return $idAnalyzerService->getPersonMedia($sessionId);
    }

    public function getMediaContent($mediaId)
    {
        $idAnalyzerService = app(IdAnalyzerService::class);
        return $idAnalyzerService->getMediaContent($mediaId);
    }

    public function sendVerificationLink(Request $request) {
        $mobile = $request->input('number');
        $lang = $request->input('lang', 'fr');

        if (!preg_match('/^\+[1-9]\d{7,14}$/', $mobile)) {
            return response()->json(['error' => 'Invalid international phone number format. Expected E.164 (e.g., +2126XXXXXXX).'], 422);
        }
        // Create ID Analyzer session and send SMS
        try {
            app()->setLocale($lang);
            $profileId = (string) config('services.id_analyzer.profile_id');
            if (empty($profileId)) {
                return response()->json(['error' => 'ID Analyzer profile is not configured.'], 500);
            }

            $idAnalyzerService = app(IdAnalyzerService::class);
            $payload = [
                'profile' => $profileId,
                'language' => $lang,
            ];
            $session = $idAnalyzerService->createSession($payload);
            if (!is_array($session) || empty($session['session_id'])) {
                $errorMessage = is_array($session) && !empty($session['error'])
                    ? $session['error']
                    : 'Failed to create verification session';

                return response()->json(['error' => $errorMessage], 500);
            }
            $to = $mobile;
            $link = $this->shortLinkService->store($session['verification_url'])->getData()->short_url;
            $sessionId =  $session['session_id'];
            $message = __('auth.veriff_message', ['link' => $link]);
            \Log::info("Verification link: " . $link);
            \Log::info("Session ID: " . $sessionId);
            $user = auth()->user();
            if ($sessionId) {
                $user = auth()->user();
                $user->update([
                'veriff_session' => $sessionId,
                'veriff_url' => $link,
                'status' => 'pending',
               ]);
            }
            $this->twilioService->sendSms($to, $message);

            return response()->json(['message' => 'Verification link sent successfully']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send verification link: ' . $e->getMessage()], 500);
        }
    }

    /**
    * Resend verification link using existing session_id
    * This uses the same ID Analyzer session that was created initially
     */
    public function resendVerificationLink(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'demande_id' => 'required|string',
        ]);

        $sessionId = $request->input('session_id');
        $demandeId = $request->input('demande_id');

        try {
            // Check cooldown (5 minutes)
            $cooldownKey = "resend_cooldown_{$sessionId}";
            $lastResend = \Cache::get($cooldownKey);
            if ($lastResend) {
                $remainingSeconds = 300 - (time() - $lastResend);
                if ($remainingSeconds > 0) {
                    $remainingMinutes = ceil($remainingSeconds / 60);
                    return response()->json([
                        'error' => "Veuillez attendre {$remainingMinutes} minute(s) avant de renvoyer le SMS",
                        'cooldown_remaining' => $remainingSeconds
                    ], 429);
                }
            }

            $phoneNumber = null;
            $personName = null;
            $demande = null;
            $shareholder = null;
            $dirigeant = null;

            // Find verification record by session_id
            // Check in Verification table first (linked to user, shareholder, or dirigeant)
            $verification = \App\Models\Verification::where('veriff_session', $sessionId)->first();

            if ($verification) {
                $phoneNumber = $verification->GSM;

                // Find which person this belongs to
                // Check if it's linked to a demande (user)
                $demande = Demande::where('id', $demandeId)
                    ->where('verification_id', $verification->id)
                    ->first();

                if ($demande) {
                    $personName = $demande->user->name ?? 'User';
                } else {
                    // Check shareholders
                    $shareholder = ShareHolder::where('demande_id', $demandeId)
                        ->where('verification_id', $verification->id)
                        ->first();

                    if ($shareholder) {
                        $personName = $shareholder->full_name;
                    } else {
                        // Check dirigeants
                        $dirigeant = DirigeantsService::where('demande_id', $demandeId)
                            ->where('verification_id', $verification->id)
                            ->first();

                        if ($dirigeant) {
                            $personName = ($dirigeant->givenName ?? '') . ' ' . ($dirigeant->surname ?? '');
                        }
                    }
                }
            } else {
                // Fallback: check directly in Demande, ShareHolder, DirigeantsService
                $demande = Demande::where('id', $demandeId)
                    ->where('veriff_session', $sessionId)
                    ->first();

                if ($demande) {
                    $phoneNumber = $demande->GSM;
                    $personName = $demande->user->name ?? 'User';
                } else {
                    $shareholder = ShareHolder::where('demande_id', $demandeId)
                        ->where('veriff_session', $sessionId)
                        ->first();

                    if ($shareholder) {
                        $phoneNumber = $shareholder->GSM;
                        $personName = $shareholder->full_name;
                    } else {
                        $dirigeant = DirigeantsService::where('demande_id', $demandeId)
                            ->where('veriff_session', $sessionId)
                            ->first();

                        if ($dirigeant) {
                            $phoneNumber = $dirigeant->verification->GSM ?? null;
                            $personName = ($dirigeant->givenName ?? '') . ' ' . ($dirigeant->surname ?? '');
                        }
                    }
                }
            }

            if (!$phoneNumber) {
                return response()->json(['error' => 'Phone number not found for this person'], 404);
            }

            // Validate phone number format
            if (!preg_match('/^\+[1-9]\d{7,14}$/', $phoneNumber)) {
                return response()->json(['error' => 'Invalid international phone number format. Expected E.164 (e.g., +2126XXXXXXX).'], 422);
            }

            $profileId = (string) config('services.id_analyzer.profile_id');
            if (empty($profileId)) {
                return response()->json(['error' => 'ID Analyzer profile is not configured.'], 500);
            }

            $idAnalyzerService = app(IdAnalyzerService::class);
            $session = $idAnalyzerService->createSession([
                'profile' => $profileId,
            ]);

            if (!is_array($session) || empty($session['session_id']) || empty($session['verification_url'])) {
                $errorMessage = is_array($session) && !empty($session['error'])
                    ? $session['error']
                    : 'Failed to create verification session';
                return response()->json(['error' => $errorMessage], 500);
            }

            $newSessionId = $session['session_id'];
            $shortLink = $this->shortLinkService->store($session['verification_url'])->getData();
            $veriffUrl = $shortLink->short_url ?? $session['verification_url'];

            if (!$verification && $phoneNumber) {
                $verification = \App\Models\Verification::where('GSM', $phoneNumber)->first();
            }

            if (!$verification && $phoneNumber) {
                $verification = \App\Models\Verification::create([
                    'GSM' => $phoneNumber,
                    'veriff_session' => $newSessionId,
                    'veriff_url' => $veriffUrl,
                ]);
            }

            if ($verification) {
                $verification->update([
                    'veriff_session' => $newSessionId,
                    'veriff_url' => $veriffUrl,
                ]);
                if ($demande && $demande->verification_id !== $verification->id) {
                    $payload = [
                        'verification_id' => $verification->id,
                        'veriff_session' => $newSessionId,
                        'veriff_url' => $veriffUrl,
                    ];
                    if (Schema::hasColumn('demandes', 'GSM')) {
                        $payload['GSM'] = $phoneNumber;
                    }
                    $demande->update($payload);
                } elseif ($shareholder && $shareholder->verification_id !== $verification->id) {
                    $shareholder->update([
                        'verification_id' => $verification->id,
                        'veriff_session' => $newSessionId,
                        'veriff_url' => $veriffUrl,
                    ]);
                } elseif ($dirigeant && $dirigeant->verification_id !== $verification->id) {
                    $dirigeant->update([
                        'verification_id' => $verification->id,
                        'veriff_session' => $newSessionId,
                        'veriff_url' => $veriffUrl,
                    ]);
                }
            } elseif (isset($demande) && $demande) {
                $demande->update([
                    'veriff_session' => $newSessionId,
                    'veriff_url' => $veriffUrl,
                ]);
            } elseif (isset($shareholder) && $shareholder) {
                $shareholder->update([
                    'veriff_session' => $newSessionId,
                    'veriff_url' => $veriffUrl,
                ]);
            } elseif (isset($dirigeant) && $dirigeant) {
                if ($dirigeant->verification) {
                    $dirigeant->verification->update([
                        'veriff_session' => $newSessionId,
                        'veriff_url' => $veriffUrl,
                    ]);
                } else {
                    $dirigeant->update([
                        'veriff_session' => $newSessionId,
                        'veriff_url' => $veriffUrl,
                    ]);
                }
            }

            // Resend SMS with new link
            $message = "Cliquez ici pour verifier votre identite via ID Analyzer : $veriffUrl";
            $this->twilioService->sendSms($phoneNumber, $message);

            // Set cooldown (5 minutes = 300 seconds)
            $newCooldownKey = "resend_cooldown_{$newSessionId}";
            \Cache::put($newCooldownKey, time(), 300);

            \Log::info("Resent verification link", [
                'session_id' => $newSessionId,
                'person' => $personName,
                'phone' => $phoneNumber
            ]);

            return response()->json([
                'message' => 'Verification link resent successfully',
                'person' => $personName,
                'session_id' => $newSessionId,
                'veriff_url' => $veriffUrl,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error resending verification link: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to resend verification link: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update phone number for a person (user, shareholder, or dirigeant)
     * Checks if verification with GSM exists, if not creates new verification with new session
     */
    public function updatePhoneNumber(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'demande_id' => 'required|string',
            'person_type' => 'required|string|in:user,shareholder,dirigeant',
            'new_phone' => 'required|string|regex:/^\+[1-9]\d{7,14}$/',
        ]);

        $sessionId = $request->input('session_id');
        $demandeId = $request->input('demande_id');
        $personType = $request->input('person_type');
        $newPhone = $request->input('new_phone');

        try {
            // Find the old verification record to identify which person we're updating
            $oldVerification = \App\Models\Verification::where('veriff_session', $sessionId)->first();

            if (!$oldVerification) {
                return response()->json(['error' => 'Old verification not found'], 404);
            }

            // Check if verification with new phone already exists in verifications table
            $existingVerification = \App\Models\Verification::where('GSM', $newPhone)->first();

            if ($existingVerification) {
                // Use existing verification - just update the verification_id for the specific person
                $newVerification = $existingVerification;
                $veriffUrl = $existingVerification->veriff_url;
            } else {
                // Create NEW verification with NEW session and URL
                $profileId = (string) config('services.id_analyzer.profile_id');
                if (empty($profileId)) {
                    return response()->json(['error' => 'ID Analyzer profile is not configured.'], 500);
                }
                $idAnalyzerService = app(IdAnalyzerService::class);
                $session = $idAnalyzerService->createSession([
                    'profile' => $profileId,
                ]);

                if (!is_array($session) || !isset($session['session_id'])) {
                    return response()->json(['error' => 'Failed to create new ID Analyzer session'], 500);
                }

                // Create short link for the new verification URL
                $veriffUrl = $this->shortLinkService->store($session['verification_url'])->getData()->short_url;

                // Create new verification record with new GSM, new session, and new URL
                $newVerification = \App\Models\Verification::create([
                    'GSM' => $newPhone,
                    'veriff_session' => $session['session_id'],
                    'veriff_url' => $veriffUrl,
                ]);
            }

            // Update ONLY the specific person being updated (isolated update)
            if ($personType === 'user') {
                $demande = Demande::where('id', $demandeId)->first();
                if (!$demande) {
                    return response()->json(['error' => 'Demande not found'], 404);
                }
                // Only update verification_id for this specific demande
                $demande->update([
                    'verification_id' => $newVerification->id,
                ]);
            } elseif ($personType === 'shareholder') {
                // Find the SPECIFIC shareholder by session_id (to isolate from others)
                $shareholder = ShareHolder::where('demande_id', $demandeId)
                    ->whereHas('verification', function($query) use ($sessionId) {
                        $query->where('veriff_session', $sessionId);
                    })
                    ->first();

                if (!$shareholder) {
                    // Fallback: find by verification_id directly
                    $shareholder = ShareHolder::where('demande_id', $demandeId)
                        ->where('verification_id', $oldVerification->id)
                        ->first();
                }

                if (!$shareholder) {
                    return response()->json(['error' => 'Shareholder not found'], 404);
                }

                // Update ONLY this specific shareholder's verification_id
                $shareholder->update([
                    'verification_id' => $newVerification->id,
                ]);
            } elseif ($personType === 'dirigeant') {
                // Find the SPECIFIC dirigeant by session_id (to isolate from others)
                $dirigeant = DirigeantsService::where('demande_id', $demandeId)
                    ->whereHas('verification', function($query) use ($sessionId) {
                        $query->where('veriff_session', $sessionId);
                    })
                    ->first();

                if (!$dirigeant) {
                    // Fallback: find by verification_id directly
                    $dirigeant = DirigeantsService::where('demande_id', $demandeId)
                        ->where('verification_id', $oldVerification->id)
                        ->first();
                }

                if (!$dirigeant) {
                    return response()->json(['error' => 'Dirigeant not found'], 404);
                }

                // Update ONLY this specific dirigeant's verification_id
                $dirigeant->update([
                    'verification_id' => $newVerification->id,
                ]);
            }

            // Send SMS to new phone number with the verification link
            $message = "Cliquez ici pour verifier votre identite via ID Analyzer : $veriffUrl";
            $this->twilioService->sendSms($newPhone, $message);

            \Log::info("Phone number updated", [
                'session_id' => $sessionId,
                'person_type' => $personType,
                'old_phone' => $oldVerification->GSM,
                'new_phone' => $newPhone,
                'new_verification_id' => $newVerification->id,
                'used_existing' => $existingVerification ? true : false
            ]);

            return response()->json([
                'message' => 'Phone number updated successfully',
                'verification_id' => $newVerification->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating phone number: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update phone number: ' . $e->getMessage()], 500);
        }
    }

    public function handle(Request $request)
        {
            $secret = env('ID_ANALYZER_SECRET_KEY');

            $rawBody = rtrim($request->getContent(), "\n");
            $signatureHeader = $request->header('x-hmac-signature');

            if (!$signatureHeader) {
                return response()->json(['error' => 'Missing signature'], 400);
            }

            \Log::info('signature: ' . $signatureHeader);
            \Log::info('raw body: '. $rawBody);

            // Verification HMAC SHA256
            $calculatedHmac = hash_hmac('sha256', $rawBody, $secret, false);

            if (!hash_equals(strtolower($calculatedHmac), $signatureHeader)) {
                \Log::warning('Webhook signature invalid');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $data = $request->json()->all();

            \Log::info('Webhook recu de ID Analyzer');

            // Exemple : recuperer sessionId et status
            $sessionId = $data['verification']['id'] ?? null;
            $status    = $data['verification']['status'] ?? null;

            if ($sessionId && $status) {
                // Exemple : mise a jour de l'utilisateur lie a la session
                switch ($status) {
                    case 'approved':
                        $statusIn = 'accepted';
                        break;
                    case 'declined':
                        $statusIn = 'refused';
                        break;
                    case 'expired':
                        $statusIn = 'pending';
                        break;
                    case 'abandoned':
                        $statusIn = 'refused';
                        break;
                    case 'resubmission_requested':
                        $statusIn = 'pending';
                        break;
                    case 'review':
                        $statusIn = 'pending';
                    default:
                        $statusIn = 'pending';
                        break;
                }
                User::where('veriff_session', $sessionId)
                    ->update([
                        'status' => $statusIn ,
                    ]);
                Demande::where('veriff_session', $sessionId)
                    ->update([
                        'status' => $statusIn ,
                    ]);
                DirigeantsService::where('veriff_session', $sessionId)
                    ->update([
                        'status' => $statusIn ,
                    ]);
                ShareHolder::where('veriff_session', $sessionId)
                    ->update([
                        'status' => $statusIn ,
                    ]);

                $verification = Verification::where('veriff_session', $sessionId)->first();
                if ($verification) {
                    Demande::where('verification_id', $verification->id)
                        ->update([
                            'status' => $statusIn,
                        ]);
                    DirigeantsService::where('verification_id', $verification->id)
                        ->update([
                            'status' => $statusIn,
                        ]);
                    ShareHolder::where('verification_id', $verification->id)
                        ->update([
                            'status' => $statusIn,
                        ]);

                    if (Schema::hasColumn('demandes', 'GSM') && !empty($verification->GSM)) {
                        Demande::whereNull('verification_id')
                            ->where('GSM', $verification->GSM)
                            ->update([
                                'verification_id' => $verification->id,
                                'status' => $statusIn,
                                'veriff_session' => $sessionId,
                            ]);
                    }
                }
            }

            return response()->json(['success' => true], 200);
        }

}
