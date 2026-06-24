<?php

namespace App\Http\Controllers;

use App\Models\Depositaire;
use App\Models\Demande;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepositaireController extends Controller
{
    public function store(Request $request)
    {
        if ($request->type === 'comptable') {
            $userName = optional($request->user())->name;
            if ((!$request->nom || !$request->prenom) && $userName) {
                $parts = preg_split('/\s+/', trim($userName));
                if (count($parts) === 1) {
                    $request->merge([
                        'prenom' => $request->prenom ?: $parts[0],
                        'nom' => $request->nom ?: $parts[0],
                    ]);
                } elseif (count($parts) > 1) {
                    $request->merge([
                        'prenom' => $request->prenom ?: $parts[0],
                        'nom' => $request->nom ?: implode(' ', array_slice($parts, 1)),
                    ]);
                }
            }
        }

        $rules = [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'type' => ['required', Rule::in(['comptable', 'gestionnaire', 'autrecompt'])],
        ];

        if ($request->type === 'autrecompt') {
            $rules += [
                'fonction' => 'required|string',
            ];
        }

        if ($request->type === 'gestionnaire') {
            $rules += [
                'date_naissance' => 'required|date',
                'lieu_naissance' => 'required|string',
                'numero_registre_national' => 'required|string',
                'domicile' => 'required|string',
            ];
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('carte_identite')) {
            $validated['carte_identite'] = $request
                ->file('carte_identite')
                ->store('cartes_identite', 'public');
        }

        $validated['demande_id'] = $request->demande_id;

        Depositaire::updateOrCreate(
            ['demande_id' => $request->demande_id],
            $validated
        );

        Demande::where('id', $request->demande_id)->update([
            'depositaire' => $validated['type'],
        ]);

        return response()->json(['success' => true]);
    }

    public function showByDemande($demandeId)
    {
        return Depositaire::where('demande_id', $demandeId)->firstOrFail();
    }
}
