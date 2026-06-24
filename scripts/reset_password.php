<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'contact@legakte.be';
$newPassword = 'Momik19811981++';

$user = \App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "User not found";
    exit;
}

$user->password = \Illuminate\Support\Facades\Hash::make($newPassword);
$user->save();

echo "Password reset OK for $email";
echo "\nPlease delete this file after use.";
