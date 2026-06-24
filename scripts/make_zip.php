<?php
$root = realpath(__DIR__ . '/../');
$zipPath = $root . DIRECTORY_SEPARATOR . 'aktly-deploy.zip';
if (file_exists($zipPath)) { unlink($zipPath); }
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    echo "ERROR: cannot create zip at $zipPath\n";
    exit(1);
}
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($files as $file) {
    $filePath = $file->getRealPath();
    // skip the generated zip itself
    if ($filePath === $zipPath) { continue; }
    // add file with relative path
    $localPath = ltrim(str_replace($root, '', $filePath), DIRECTORY_SEPARATOR);
    $zip->addFile($filePath, $localPath);
}
$zip->close();
echo "ZIP_CREATED:" . filesize($zipPath) . "\n";
