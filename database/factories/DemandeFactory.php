<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Demande>
 */
class DemandeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::query()->inRandomOrder()->first() ?? User::factory()->create();

        return [
            'user_id' => $user->id,
            'enterprise_number' => $this->faker->numerify('0#########'),
            'langue_entreprise' => 'fr',
            'bce_data' => json_encode([
                'name' => $this->faker->company(),
            ]),
            'in_enterprise' => false,
            'status' => 'resolved',
            'progress' => 100,
            'address_service' => true,
            'dirigeants_service' => true,
            'cession_parts_service' => true,
        ];
    }
}
