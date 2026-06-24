<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$archives = [
    'aktly_deploy.tar.gz',
    '../aktly_deploy.tar.gz',
    'aktly-deploy.zip',
    '../aktly-deploy.zip',
];

$archive = null;
foreach ($archives as $candidate) {
    if (file_exists($candidate)) {
        $archive = $candidate;
        break;
    }
}

$destination = __DIR__ . '/';

echo "<h2>Deployment Script</h2>";
echo "Current directory: " . getcwd() . "<br>";

if (!$archive) {
    die("<h3 style='color:red'>Error: Archive not found. Tried: " . implode(', ', $archives) . "</h3>");
}

echo "Found archive: $archive (Size: " . round(filesize($archive) / 1024 / 1024, 2) . " MB)<br>";
echo "Extracting to: $destination<br>";

$success = false;

// ZIP extraction
if (str_ends_with($archive, '.zip')) {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($archive) === true) {
            $zip->extractTo($destination);
            $zip->close();
            echo "<h3 style='color:green'>Success! Files extracted using ZipArchive.</h3>";
            $success = true;
        } else {
            echo "<div style='color:red'>ZipArchive failed to open archive.</div>";
        }
    } else {
        echo "<div style='color:orange'>ZipArchive not available.</div>";
    }
}

// TAR.GZ extraction
if (!$success && str_ends_with($archive, '.tar.gz')) {
    try {
        if (class_exists('PharData')) {
            echo "Attempting extraction using PharData...<br>";
            $phar = new PharData($archive);
            $phar->extractTo($destination, null, true);
            echo "<h3 style='color:green'>Success! Files extracted using PharData.</h3>";
            $success = true;
        } else {
            echo "PharData class not available.<br>";
        }
    } catch (Exception $e) {
        echo "<div style='color:orange'>PharData failed: " . $e->getMessage() . "</div>";
    }
}

if ($success) {
    echo "<hr><strong>Next Steps:</strong><br>";
    echo "1. Verify your site is working.<br>";
    echo "2. Delete the archive file.<br>";
    echo "3. Delete this script file.<br>";
} else {
    echo "<hr><h3 style='color:red'>Extraction failed. Please extract manually.</h3>";
}
