<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckCredits
{
    /**
     * Example usage in a route:
     *   ->middleware(CheckCredits::class)          // default cost = 1
     *   ->middleware(CheckCredits::class.':5')     // cost = 5
     */
   // app/Http/Middleware/CheckCredits.php
public function handle(Request $request, Closure $next, int $cost = 1)
{
    $user = $request->user();

    // Check if this is a resume operation by looking for demande_id in request
    $demandeId = $request->input('demande_id');

    if ($demandeId) {
        // Check if this demande already exists and belongs to the user
        $existingDemande = \App\Models\Demande::where('id', $demandeId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingDemande) {
            // This is a resume operation, skip credit check
            \Log::info('Resuming existing demande, skipping credit check', [
                'demande_id' => $demandeId,
                'user_id' => $user->id
            ]);
            return $next($request);
        }
    }

    if (!$user || !$user->hasCredits($cost)) {
        return response()->json([
            'error'   => 'not_enough_credits',
            'message' => 'Pas assez de crédits'
        ], 402);                       // ← no redirect
    }

    $user->spend($cost);
    return $next($request);
}

}
