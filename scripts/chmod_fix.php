<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mode = 0775;
$targets = [
    realpath(__DIR__ . '/../storage'),
    realpath(__DIR__ . '/../bootstrap/cache'),
];

function chmod_recursive(string $path, int $mode, array &$log): void
{
    if (!$path || !file_exists($path)) {
        $log[] = "SKIP: path not found";
        return;
    }

    $log[] = "SET: $path";
    @chmod($path, $mode);

    if (is_dir($path)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $p = $item->getPathname();
            @chmod($p, $mode);
        }
    }
}

echo "<h2>Permission Fix</h2>";
echo "Mode: " . decoct($mode) . "<br>";

$allLogs = [];
foreach ($targets as $target) {
    $log = [];
    chmod_recursive((string)$target, $mode, $log);
    $allLogs[] = $log;
}

echo "<pre>" . htmlspecialchars(print_r($allLogs, true)) . "</pre>";
echo "<strong>Done.</strong> Please delete this file after use.";
