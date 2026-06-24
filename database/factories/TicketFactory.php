<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Demande;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $demande = Demande::query()->inRandomOrder()->first() ?? Demande::factory()->create();

        return [
            'demande_id' => $demande->id,
            'type' => 'support',
            'description' => $this->faker->sentence(10),
            'nom' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
        ];
    }
}
