<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /api/profile
     * Auth: Sanctum token
    * Response: { id, name, email, langue_user, subscription_status, subscription_plan, applied_coupon_code, coupon_expires_at, dossiers_remaining }
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // Base user data
        $userData = $user->only([
            'id',
            'name',
            'email',
            'langue_user',
            'status',
            'subscription_status',
            'subscription_plan',
            'cumulative_dossier_count',
            'applied_coupon_code',
            'coupon_applied_at',
        ]);
        
        // Add coupon details if user has applied coupon
        if ($user->applied_coupon_code) {
            $coupon = \App\Models\Coupon::where('code', $user->applied_coupon_code)->first();
            if ($coupon) {
                // Get current usage by coupon_id
                $usage = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
                    ->where('user_id', $user->id)
                    ->first();
                
                $dossiers_used = $usage ? $usage->dossiers_used : 0;
                $dossiers_remaining = max(0, $coupon->max_dossiers - $dossiers_used);
                
                $userData['coupon_expires_at'] = $coupon->expires_at;
                $userData['coupon_max_dossiers'] = $coupon->max_dossiers;
                $userData['coupon_dossiers_used'] = $dossiers_used;
                $userData['coupon_dossiers_remaining'] = $dossiers_remaining;
            }
        }
        
        // Add dossiers_remaining using the model's method
        $userData['dossiers_remaining'] = $user->getDossiersRemaining();
        $userData['plan_quota'] = $user->getPlanQuota();
        
        return response()->json($userData);
    }

    /**
     * PUT /api/profile
     * Auth: Sanctum token
     * Body: { name }
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $user->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Profile updated',
            'name' => $user->name,
        ]);
    }
}
