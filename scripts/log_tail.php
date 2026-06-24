<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$path = __DIR__ . '/../storage/logs/laravel.log';
$lines = isset($_GET['lines']) ? (int) $_GET['lines'] : 200;
$lines = max(10, min($lines, 1000));

if (!file_exists($path)) {
    echo "Log file not found.";
    exit;
}

$data = @file($path, FILE_IGNORE_NEW_LINES);
if ($data === false) {
    echo "Unable to read log file.";
    exit;
}

$tail = array_slice($data, -$lines);
echo "<pre>" . htmlspecialchars(implode("\n", $tail)) . "</pre>";
echo "<strong>Done.</strong> Please delete this file after use.";
