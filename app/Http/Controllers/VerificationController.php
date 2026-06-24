<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use App\Models\Demande;
use Illuminate\Http\Request;
use App\Services\TwilioService;
use App\Services\IdAnalyzerService;
use App\Services\VeriffService;
use App\Services\ShortLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;


class VerificationController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     */
        protected $twilioService;
    protected $idAnalyzerService;
    protected $shortLinkService;
    protected $veriffService;
        public function __construct(IdAnalyzerService $idAnalyzerService,TwilioService $twilioService,ShortLinkService $shortLinkService,VeriffService $veriffService)
        {
        $this->twilioService = $twilioService;
        $this->idAnalyzerService = $idAnalyzerService;
        $this->shortLinkService = $shortLinkService;
        $this->veriffService = $veriffService;
    }
    public function index()
    {
        return Verification::all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate( [
            'GSM' => 'required|string|regex:/^\+[1-9]\d{7,14}$/',
        ]);

        try {
            $verification = Verification::where('GSM', $request->GSM)->first();

            // Pre-fill the Veriff session with the selected dirigeant's name (helps identity matching).
            $firstName = null;
            $lastName = null;
            if ($request->filled('dirigeant_id')) {
                $dirigeant = \App\Models\DirigeantsService::find($request->input('dirigeant_id'));
                if ($dirigeant) {
                    $firstName = $dirigeant->givenName;
                    $lastName = $dirigeant->surname;
                }
            }

            // Create a Veriff verification session (HMAC-signed).
            $session = $this->veriffService->createSession([
                'vendorData' => (string) $request->input('demande_id', ''),
                'firstName' => $firstName,
                'lastName' => $lastName,
            ]);
            if (!is_array($session) || empty($session['session_id']) || empty($session['verification_url'])) {
                $errorMessage = is_array($session) && !empty($session['error'])
                    ? $session['error']
                    : 'Failed to create verification session';
                return response()->json(['error' => $errorMessage], 500);
            }

            // Create short link for the verification URL
            $veriffUrl = $this->shortLinkService->store($session['verification_url'])->getData()->short_url;

            // Create or refresh verification with shortened URL
            if ($verification) {
                $verification->update([
                    'veriff_session' => $session['session_id'],
                    'veriff_url' => $veriffUrl,
                ]);
            } else {
                $verification = Verification::create([
                    'GSM' => $request->GSM,
                    'veriff_session' => $session['session_id'],
                    'veriff_url' => $veriffUrl,
                ]);
            }

            $message = "Cliquez ici pour vérifier votre identité via Veriff : $veriffUrl";
            try {
                $this->twilioService->sendSms($request->GSM, $message);
            } catch (\Exception $e) {
                \Log::error('Verification SMS failed', [
                    'error' => $e->getMessage(),
                    'gsm' => $request->GSM,
                ]);
            }

            $statusCode = $verification->wasRecentlyCreated ? 201 : 200;

            // Link verification to demande if provided
            if ($request->filled('demande_id')) {
                $demande = Demande::find($request->input('demande_id'));
                if ($demande) {
                    $payload = ['verification_id' => $verification->id];
                    if (Schema::hasColumn('demandes', 'GSM')) {
                        $payload['GSM'] = $request->GSM;
                    }
                    $demande->update($payload);
                }
            }

            // Link verification to dirigeant if provided
            if ($request->filled('dirigeant_id')) {
                $dirigeant = \App\Models\DirigeantsService::find($request->input('dirigeant_id'));
                if ($dirigeant) {
                    $dirigeant->update(['verification_id' => $verification->id]);
                    \Log::info("✅ Linked Verification {$verification->id} to Dirigeant {$dirigeant->id}");
                }
            }

            // Link verification to shareholder if provided
            if ($request->filled('shareholder_id')) {
                $shareholder = \App\Models\ShareHolder::find($request->input('shareholder_id'));
                if ($shareholder) {
                    $shareholder->update(['verification_id' => $verification->id]);
                    \Log::info("✅ Linked Verification {$verification->id} to Shareholder {$shareholder->id}");
                }
            }

            return response()->json($verification, $statusCode);
        } catch (\Exception $e) {
            \Log::error('Verification store failed', [
                'error' => $e->getMessage(),
                'gsm' => $request->GSM,
            ]);
            return response()->json(['error' => 'Failed to send verification SMS'], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate( [
            'GSM' => 'required|string|regex:/^\+[1-9]\d{7,14}$/|unique:verifications,GSM,'.$id,
        ]);
        $verification = Verification::find($id);
        $verification->update($request->all());
        return $verification;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Proxy a Veriff media object (ID front/back, face) through the backend.
     * Veriff media endpoints require X-AUTH-CLIENT + X-HMAC-SIGNATURE headers, so the
     * browser can't fetch them directly — it requests /api/veriff/media/{id} instead.
     */
    public function media(string $mediaId)
    {
        return $this->veriffService->getMediaContent($mediaId);
    }
}
