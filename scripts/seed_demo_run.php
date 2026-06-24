<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\EmailVerification;

$u = User::updateOrCreate(
    ['email' => 'demo@aktly.test'],
    [
        'name' => 'Demo Utilisateur',
        'password' => bcrypt('password'),
        'langue_user' => 'fr',
        'credits' => 5,
        'email_verified_at' => now(),
    ]
);

EmailVerification::updateOrCreate(
    ['user_id' => $u->id],
    ['token' => bin2hex(random_bytes(16)), 'verified_at' => now()]
);

$u->tokens()->delete();
$token = $u->createToken('preview')->plainTextToken;

echo 'TOKEN=' . $token . PHP_EOL;
echo 'USER_ID=' . $u->id . PHP_EOL;
