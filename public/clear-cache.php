<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$token = trim((string) ($_GET['token'] ?? ''));
$expected = trim((string) env('MIGRATE_TOKEN', ''));

if ($expected === '') {
    $expected = trim((string) getenv('MIGRATE_TOKEN'));
}

if ($expected === '') {
    $envPath = dirname(__DIR__) . '/.env';
    if (is_file($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            if (strpos($line, 'MIGRATE_TOKEN=') === 0) {
                $value = substr($line, strlen('MIGRATE_TOKEN='));
                $expected = trim($value, " \t\n\r\0\x0B\"");
                break;
            }
        }
    }
}

if ($expected === '' || !hash_equals($expected, $token)) {
    $tail = $expected !== '' ? substr($expected, -4) : '';
    $tokenTail = $token !== '' ? substr($token, -4) : '';
    
    \Log::warning('Clear-cache forbidden', [
        'expected_len' => strlen($expected),
        'expected_tail' => $tail,
        'token_len' => strlen($token),
        'token_tail' => $tokenTail,
    ]);
    http_response_code(403);
    echo 'Forbidden' . "\n";
    echo 'expected_len=' . strlen($expected) . "\n";
    echo 'expected_tail=' . $tail . "\n";
    echo 'token_len=' . strlen($token) . "\n";
    echo 'token_tail=' . $tokenTail . "\n";
    exit;
}

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$commands = [
    'config:clear',
    'cache:clear',
    'route:clear',
    'view:clear',
];

foreach ($commands as $command) {
    $kernel->call($command);
}

$kernel->terminate(new Illuminate\Http\Request(), new Illuminate\Http\Response());

echo 'OK';
