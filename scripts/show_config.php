<?php
$autoload = __DIR__ . '/../vendor/autoload.php';
if (! file_exists($autoload)) {
	echo "ERROR: vendor/autoload.php not found\n";
	exit(1);
}
require $autoload;
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "database.default=" . config('database.default') . PHP_EOL;
echo "session.driver=" . config('session.driver') . PHP_EOL;
echo "cache.default=" . config('cache.default') . PHP_EOL;
echo "cache.stores.default=" . (config('cache.stores.' . config('cache.default') . '.driver') ?? 'n/a') . PHP_EOL;
echo "env.CACHE_STORE=" . getenv('CACHE_STORE') . PHP_EOL;
echo "env.DB_CONNECTION=" . getenv('DB_CONNECTION') . PHP_EOL;
echo "env.SESSION_DRIVER=" . getenv('SESSION_DRIVER') . PHP_EOL;
