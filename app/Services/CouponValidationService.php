<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CouponValidationService
{
    /**
     * Validate if a coupon can be used by a user
     */
    public function validate(string $code, int $userId): array
    {
        $coupon = Coupon::where('code', strtoupper($code))
            ->where('active', true)
            ->first();

        if (!$coupon) {
            return ['valid' => false, 'message' => 'Coupon not found or inactive'];
        }

        if ($coupon->isExpired()) {
            return ['valid' => false, 'message' => 'Coupon has expired'];
        }

        // Check max users
        if ($coupon->max_users) {
            $usedCount = $coupon->usages()->distinct('user_id')->count();
            if ($usedCount >= $coupon->max_users) {
                return ['valid' => false, 'message' => 'Coupon usage limit reached'];
            }
        }

        // Check if user already used this coupon
        $existing = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return ['valid' => false, 'message' => 'You have already used this coupon'];
        }

        return [
            'valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'description' => $coupon->description,
                'stripe_coupon_id' => $coupon->stripe_coupon_id,
                'max_dossiers' => $coupon->max_dossiers,
                'dossiers_remaining' => $coupon->dossiersRemaining(),
                'expires_at' => $coupon->expires_at,
            ],
        ];
    }

    /**
     * Apply coupon to user account
     */
    public function applyCoupon(User $user, string $code): array
    {
        $validation = $this->validate($code, $user->id);

        if (!$validation['valid']) {
            return [
                'applied' => false,
                'message' => $validation['message'],
            ];
        }

        DB::transaction(function () use ($user, $code) {
            $coupon = Coupon::where('code', strtoupper($code))->first();
            
            // Create initial usage record
            CouponUsage::firstOrCreate(
                ['coupon_id' => $coupon->id, 'user_id' => $user->id],
                ['used_at' => now(), 'dossiers_used' => 0]
            );

            // Apply coupon to user
            $user->update([
                'applied_coupon_code' => $code,
                'coupon_applied_at' => now(),
            ]);
        });

        return [
            'applied' => true,
            'message' => 'Coupon applied successfully',
            'coupon' => $validation['coupon'],
        ];
    }

    /**
     * Check if user's applied coupon is still valid
     */
    public function isUserCouponValid(User $user): bool
    {
        if (!$user->applied_coupon_code) {
            return false;
        }

        $coupon = Coupon::where('code', strtoupper($user->applied_coupon_code))
            ->where('active', true)
            ->first();

        if (!$coupon) {
            return false;
        }

        if ($coupon->isExpired()) {
            $this->removeCouponFromUser($user, 'expired', false); // Don't delete usage for expired
            return false;
        }

        // Check dossier limit
        if ($coupon->max_dossiers) {
            $usage = $coupon->usages()
                ->where('user_id', $user->id)
                ->first();

            if ($usage && $usage->dossiers_used >= $coupon->max_dossiers) {
                $this->removeCouponFromUser($user, 'limit_reached', false); // Don't delete usage for limit
                return false;
            }
        }

        return true;
    }

    /**
     * Remove coupon from user account
     */
    public function removeCouponFromUser(User $user, string $reason = 'expired', bool $deleteUsage = true): void
    {
        $appliedCode = $user->applied_coupon_code;

        $user->update([
            'applied_coupon_code' => null,
            'coupon_applied_at' => null,
        ]);

        // Delete the usage record so user can apply coupon again (if user-initiated removal)
        if ($deleteUsage && $appliedCode) {
            $coupon = Coupon::where('code', strtoupper($appliedCode))->first();
            if ($coupon) {
                CouponUsage::where('coupon_id', $coupon->id)
                    ->where('user_id', $user->id)
                    ->delete();
            }
        }

        \Illuminate\Support\Facades\Log::info("Coupon removed from user {$user->id}: {$reason}");
    }

    /**
     * Record usage when dossier is created
     */
    public function recordDossierUsage(string $couponCode, int $userId, int $dossierCount = 1): array
    {
        $coupon = Coupon::where('code', strtoupper($couponCode))->first();

        if (!$coupon) {
            return ['recorded' => false, 'dossiers_used' => 0];
        }

        $result = DB::transaction(function () use ($coupon, $userId, $dossierCount) {
            $usage = CouponUsage::firstOrCreate(
                ['coupon_id' => $coupon->id, 'user_id' => $userId],
                ['used_at' => now()]
            );

            $dossiers_allowed = $dossierCount;

            // Check if coupon dossier limit exceeded
            if ($coupon->max_dossiers) {
                $currentUsed = $usage->dossiers_used + $dossierCount;
                if ($currentUsed > $coupon->max_dossiers) {
                    // Only allow up to the limit
                    $dossiers_allowed = max(0, $coupon->max_dossiers - $usage->dossiers_used);
                }
            }

            $usage->increment('dossiers_used', $dossiers_allowed);

            return [
                'recorded' => true,
                'dossiers_used' => $dossiers_allowed,
                'total_used' => $usage->fresh()->dossiers_used,
                'max_dossiers' => $coupon->max_dossiers,
            ];
        });

        return $result;
    }

    /**
     * Cleanup expired coupons from user accounts (should be run in scheduler)
     */
    public function cleanupExpiredCoupons(): int
    {
        $users = User::whereNotNull('applied_coupon_code')->get();
        $cleaned = 0;

        foreach ($users as $user) {
            if (!$this->isUserCouponValid($user)) {
                $this->removeCouponFromUser($user, 'cleanup_task');
                $cleaned++;
            }
        }

        return $cleaned;
    }
}
