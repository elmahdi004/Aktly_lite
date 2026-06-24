<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\StripeUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DossierCountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dossier_count_increments_on_create(): void
    {
        $user = User::factory()->create([
            'subscription_status' => 'active',
            'subscription_plan' => 'starter',
            'cumulative_dossier_count' => 0,
        ]);

        $usageMock = $this->mock(StripeUsageService::class);
        $usageMock->shouldNotReceive('reportExtraDossier');

        $payload = [
            'bce_data' => ['lang_entre' => 'fr'],
            'enterprise_number' => '0123456789',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/demandes', $payload);

        $response->assertStatus(200);
        $this->assertEquals(1, $user->fresh()->cumulative_dossier_count);
    }

    public function test_usage_reporting_when_quota_exceeded(): void
    {
        $user = User::factory()->create([
            'subscription_status' => 'active',
            'subscription_plan' => 'starter',
            'cumulative_dossier_count' => 3,
        ]);

        $usageMock = $this->mock(StripeUsageService::class);
        $usageMock->shouldReceive('reportExtraDossier')->once();

        $payload = [
            'bce_data' => ['lang_entre' => 'fr'],
            'enterprise_number' => '0123456789',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/demandes', $payload);

        $response->assertStatus(200);
        $this->assertEquals(4, $user->fresh()->cumulative_dossier_count);
    }
}
