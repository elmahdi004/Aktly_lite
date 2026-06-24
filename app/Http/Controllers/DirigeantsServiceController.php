<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\DirigeantsService;
use App\Http\Requests\StoreDirigeantsServiceRequest;
use App\Http\Requests\UpdateDirigeantsServiceRequest;
use App\Models\Verification;
use App\Services\IdAnalyzerService;
use App\Services\TwilioService;
use App\Models\ShareHolder;
use App\Services\ShortLinkService;
use Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class DirigeantsServiceController extends Controller
{
    public function __construct(
        protected IdAnalyzerService $idAnalyzerService,
        protected TwilioService $twilioService,
        protected ShortLinkService $shortLinkService
    ) {}

    /*──────────────────────────────────────────────────────────────*/
    /*  STORE                                                      */
    /*──────────────────────────────────────────────────────────────*/

    /**
     * Store a dirigeant (manual OR BCE import).
     * • Always burns EXACTLY 1 credit per HTTP request.
     * • Returns 402 JSON if the user lacks credits.
     */
    public function store(StoreDirigeantsServiceRequest $request)
    {
        $user    = $request->user();                       // Sanctum auth
        $demandeId = $request->input('demande_id');
        $demande = Demande::find($demandeId);

        /* 1 - Guards ──────────────────────────────────────────── */
        if (!$demande) {
            \Log::warning("❌ Demande {$request->demande_id} introuvable");
            return response()->json(['error' => 'Demande not found'], 404);
        }

        // Always check credits for adding dirigeants (both new and resumed demandes)
        // User must pay 1 credit per dirigeant added



        /* 3 - Create dirigeant(s)  ───────────────────────────── */
        $hasRcColumn = Schema::hasColumn('dirigeants_services', 'rc');
        $hasCarteIdentiteColumn = Schema::hasColumn('dirigeants_services', 'carte_identite');
        $hasGsmColumn = Schema::hasColumn('dirigeants_services', 'gsm');

        if ($request->boolean('is_not_bce')) {

            if ($request->hasFile('carte_identite')) {
                $path = $request->file('carte_identite')
                    ->store('dirigeants/cartes_identite', 'public');
            }


            // check if the GSM exists in verification table
            $payload = [
                'surname'        => $request->input('surname'),
                'givenName'      => $request->input('givenName'),
                'function'       => $request->input('function'),
                'is_modified'    => 1,
            ];
            if ($hasRcColumn) {
                $payload['rc'] = $request->input('rc');
            }
            if ($hasCarteIdentiteColumn) {
                $payload['carte_identite'] = $path ?? null;
            }
            if ($hasGsmColumn) {
                $payload['gsm'] = $request->input('gsm');
            }

            $dirigeant = $demande->dirigeants()->create($payload);

            // $this->updateInfoVeriff($dirigeant);

            \Log::info("➕ Dirigeant {$dirigeant->id} (manuel) créé pour Demande {$demande->id}");
            return response()->json($dirigeant->fresh(), 201);
        } else {
            /* BCE import branch (can create many) -------------- */
            $data = json_decode($demande->bce_data, true);
            if (!is_array($data)) {
                
                \Log::warning("ℹ️ BCE data manquante ou invalide pour Demande {$demande->id}");
                return response()->json([], 200);
            }

            // Normalize function list (BCE may return a single object)
            $functions = data_get($data, 'function', []);
            if (!is_array($functions)) {
                $functions = [$functions];
            } elseif (!empty($functions) && array_keys($functions) !== range(0, count($functions) - 1)) {
                $functions = [$functions];
            }

            $dirigeants = collect($functions)->map(function ($d) use ($demande, $hasRcColumn) {
                $row = [
                    'demande_id' => $demande->id,
                    'surname'    => data_get($d, 'person.surname') ?? '',
                    'givenName'  => data_get($d, 'person.givenName') ?? '',
                    'function'   => data_get($d, 'description.value'),
                ];
                if ($hasRcColumn) {
                    $row['rc'] = data_get($d, 'person.rc');
                }
                return $row;
            })->filter(function ($row) {
                return (!empty($row['surname']) || !empty($row['givenName']))
                    && !empty($row['function']);
            })->values()->all();

            if (count($dirigeants) === 0) {
                \Log::warning("ℹ️ Aucun dirigeant BCE trouvé pour Demande {$demande->id}");
                return response()->json([], 200);
            }

            $created = $demande->dirigeants()->createMany($dirigeants);
            \Log::info("➕ Créé " . count($created) . " dirigeants BCE pour Demande {$demande->id}");
            return response()->json($created, 201);
        }
    }

    /*──────────────────────────────────────────────────────────────*/
    /*  UPDATE (unchanged)                                          */
    /*──────────────────────────────────────────────────────────────*/

    public function update(
        UpdateDirigeantsServiceRequest $request,
        DirigeantsService $dirigeantsService
    ) {
        // If GSM is being updated, handle verification logic
        $data = $request->all();
        if ($request->hasFile('carte_identite')) {
            if ($dirigeantsService->carte_identite) {
                Storage::disk('public')->delete($dirigeantsService->carte_identite);
            }

            $data['carte_identite'] = $request->file('carte_identite')
                ->store('dirigeants/cartes_identite', 'public');
        }
        DirigeantsService::where('id', $dirigeantsService->id)
            ->update([
                'surname'     => $data['surname'] ?? $dirigeantsService->surname,
                'givenName'   => $data['givenName'] ?? $dirigeantsService->givenName,
                'function'    => $data['function'] ?? $dirigeantsService->function,
                // 'is_modified'=> 1,
                'rc' => $data['rc'] ?? $dirigeantsService->rc,
                'carte_identite' => $data['carte_identite'] ?? $dirigeantsService->carte_identite,
                'is_deleted' => array_key_exists('is_deleted', $data) ? (int) $data['is_deleted'] : $dirigeantsService->is_deleted,
                'gsm' => $data['gsm'] ?? $dirigeantsService->gsm,
            ]);
        $dirigeant = DirigeantsService::find($dirigeantsService->id);
        // $this->updateInfoVeriff($dirigeant);
        return response()->json($dirigeant->fresh());
    }

    /*──────────────────────────────────────────────────────────────*/
    /*  Boilerplate stubs                                           */
    /*──────────────────────────────────────────────────────────────*/

    public function index() {}
    public function create() {}
    public function show(DirigeantsService $dirigeantsService) {}
    public function edit(DirigeantsService $dirigeantsService) {}
    public function destroy($id)
    {
        // DirigeantsService::destroy($id);
        $dirigeant = DirigeantsService::find($id);
        if (!$dirigeant) {
            return response()->json(['error' => 'Dirigeant not found'], 404);
        }
        $dirigeant->update([
            'is_deleted' => 1
        ]);
        return response()->json(['message' => 'Dirigeant deleted successfully'], 200);
    }
}
