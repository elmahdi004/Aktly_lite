<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\ShareHolder;
use App\Http\Requests\StoreShareHolderRequest;
use App\Http\Requests\UpdateShareHolderRequest;
use App\Services\ShortLinkService;
use Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ShareHolderController extends Controller
{
    public function __construct(
        protected ShortLinkService $shortLinkService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreShareHolderRequest $request)
    {
        foreach ($request['shareholders'] as $shareHolder) {
            $path = null;

            if (isset($shareHolder['carte_identite']) && $shareHolder['carte_identite'] instanceof \Illuminate\Http\UploadedFile) {
                $path = $shareHolder['carte_identite']
                    ->store('shareHolders/cartes_identite', 'public');
            }
            $shareHolderNv = ShareHolder::create([
                'demande_id' => $request['demande_id'],
                'full_name' => $shareHolder['full_name'],
                'shares' => $shareHolder['shares'],
                'shares_old' => (isset($request['is_origin']) && $request['is_origin'] == true) ? $shareHolder['shares'] : $shareHolder['shares_old'],
                'is_origin' => isset($request['is_origin']) == true ? $request['is_origin'] : false,
                'rc' => $shareHolder['rc'] ?? '',
                'carte_identite' => $path,
                'gsm' => $shareHolder['gsm'] ?? null,
            ]);

        }
        $demande = Demande::find($request['demande_id']);
        $demande->update([
            'nb_total_actions' => $request['total_shares'],
            'pub_text' => null,
            'extrait_pub_text' => null,
        ]);
        return ShareHolder::where('demande_id', $request['demande_id'])->get();
    }

    /**
     * Display the specified resource.
     */
    public function show(ShareHolder $shareHolder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShareHolder $shareHolder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $shareHolder)
{
    $validated = $request->validate([
        'full_name' => 'required|string|max:255',
        'shares' => 'required|numeric|min:0',
        'shares_old' => 'nullable|numeric|min:0',
        'rc' => 'nullable|string|max:255',
        'carte_identite' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        'gsm' => 'nullable|string|max:32',
    ]);

    $shareHolderModel = ShareHolder::findOrFail($shareHolder);

    // gestion fichier
    if ($request->hasFile('carte_identite')) {
        if ($shareHolderModel->carte_identite) {
            Storage::disk('public')->delete($shareHolderModel->carte_identite);
        }

        $shareHolderModel->carte_identite = $request->file('carte_identite')
            ->store('shareHolders/cartes_identite', 'public');
    }

    // update sécurisé
    $shareHolderModel->update([
        'full_name' => $validated['full_name'],
        'shares' => $validated['shares'],
        'shares_old' => $validated['shares_old'] ?? null,
        'rc' => $validated['rc'] ?? null,
        'carte_identite' => $shareHolderModel->carte_identite,
        'gsm' => $validated['gsm'] ?? null,
    ]);

    if ($shareHolderModel->demande) {
        $shareHolderModel->demande->update([
            'pub_text' => null,
            'extrait_pub_text' => null,
        ]);
    }

    return response()->json($shareHolderModel->fresh());
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //delete the share holder
        $shareHolder = ShareHolder::find($id);
        if ($shareHolder) {
            if ($shareHolder->demande) {
                $shareHolder->demande->update([
                    'pub_text' => null,
                    'extrait_pub_text' => null,
                ]);
            }
            $shareHolder->delete();
        }

        return response()->json(['message' => 'Shareholder deleted successfully.']);
    }
}
