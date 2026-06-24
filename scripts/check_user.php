<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
if ($email === '') {
    echo "<h3>Missing email</h3>";
    exit;
}

$user = \App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "<h3>User not found</h3>";
    exit;
}

echo "<h3>User found</h3>";
echo "ID: {$user->id}<br>";
echo "Email: {$user->email}<br>";
echo "Name: {$user->name}<br>";
if (isset($user->email_verified_at)) {
    echo "Email verified: " . ($user->email_verified_at ? 'yes' : 'no') . "<br>";
}
if (isset($user->role)) {
    echo "Role: {$user->role}<br>";
}
echo "<strong>Done.</strong> Please delete this file after use.";
