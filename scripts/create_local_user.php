<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\Hash;

$email = 'contact@legakte.be';
$password = 'Momik19811981++';

User::updateOrCreate(
    ['email' => $email],
    [
        'name' => 'Client',
        'langue_user' => 'fr',
        'password' => Hash::make($password),
    ]
);

EmailVerification::updateOrCreate(
    ['user_id' => User::where('email', $email)->value('id')],
    [
        'token' => bin2hex(random_bytes(16)),
        'verified_at' => now(),
    ]
);

User::where('email', $email)->update([
    'is_admin' => false,
    'credits' => 50,
]);

echo "Client user created/updated: {$email}\n";
