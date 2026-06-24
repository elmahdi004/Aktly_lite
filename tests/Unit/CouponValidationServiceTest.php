<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Services\CouponValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CouponValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_max_users_is_enforced(): void
    {
        $coupon = Coupon::create([
            'code' => 'LIMIT1',
            'max_users' => 1,
            'active' => true,
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user1->id,
            'dossiers_used' => 1,
            'used_at' => now(),
        ]);

        $service = new CouponValidationService();
        $result = $service->validate('LIMIT1', $user2->id);

        $this->assertFalse($result['valid']);
    }

    public function test_coupon_dossier_limit_is_respected(): void
    {
        $coupon = Coupon::create([
            'code' => 'DOS2',
            'max_dossiers' => 2,
            'active' => true,
        ]);

        $user = User::factory()->create();

        $service = new CouponValidationService();
        $service->recordDossierUsage('DOS2', $user->id, 1);
        $service->recordDossierUsage('DOS2', $user->id, 5);

        $usage = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertEquals(2, $usage->dossiers_used);
    }
}
