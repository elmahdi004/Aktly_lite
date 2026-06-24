<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Demande;
use App\Models\User;

class DemandeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'contact@legakte.be')->first()
            ?? User::factory()->create([
                'name' => 'Client',
                'email' => 'contact@legakte.be',
                'langue_user' => 'fr',
            ]);

        Demande::factory()->count(31)->create([
            'user_id' => $user->id,
        ]);
    }
}
