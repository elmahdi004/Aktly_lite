<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! User::where('email', 'contact@legakte.be')->exists()) {
            User::factory()->create([
                'name' => 'Admin',
                'email' => 'contact@legakte.be',
                'langue_user' => 'fr',
            ]);
        }

        if (! User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'langue_user' => 'fr',
            ]);
        }

        $this->call([
            DemandeSeeder::class,
            TicketSeeder::class,
        ]);
    }
}
