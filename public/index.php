<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine application layout (Layout A: app outside webroot, Layout B: all inside webroot)
$maintenanceCandidates = [
    __DIR__ . '/../storage/framework/maintenance.php',
    __DIR__ . '/storage/framework/maintenance.php',
];

foreach ($maintenanceCandidates as $maintenance) {
    if (file_exists($maintenance)) {
        require $maintenance;
        break;
    }
}

// Register the Composer autoloader (try inside webroot first, then parent)
$autoloadCandidates = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
];

$autoloadIncluded = false;
foreach ($autoloadCandidates as $autoload) {
    if (file_exists($autoload)) {
        require $autoload;
        $autoloadIncluded = true;
        break;
    }
}

if ($autoloadIncluded === false) {
    http_response_code(500);
    echo 'Autoloader not found. Ensure the vendor directory is uploaded.';
    exit;
}

// Bootstrap Laravel and handle the request
$bootstrapCandidates = [
    __DIR__ . '/bootstrap/app.php',
    __DIR__ . '/../bootstrap/app.php',
];

$app = null;
foreach ($bootstrapCandidates as $bootstrap) {
    if (file_exists($bootstrap)) {
        $app = require_once $bootstrap;
        break;
    }
}

if ($app === null) {
    http_response_code(500);
    echo 'Bootstrap file not found. Check application paths.';
    exit;
}

// * AGGRESSIVE FIX: Handle /storage/ requests directly if route fails
$request = Request::capture();
$uri = $request->getRequestUri();

// * If it's a storage request and route might fail, serve file directly
if (strpos($uri, '/storage/') === 0) {
    $path = substr($uri, 9); // Remove '/storage/' prefix
    $fullPath = __DIR__ . '/../storage/app/public/' . $path;
    
    // * Security: Prevent directory traversal
    $fullPath = realpath($fullPath);
    $storageRoot = realpath(__DIR__ . '/../storage/app/public');
    
    if ($fullPath && strpos($fullPath, $storageRoot) === 0 && file_exists($fullPath) && is_file($fullPath)) {
        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: public, max-age=31536000');
        readfile($fullPath);
        exit;
    }
}

$app->handleRequest($request);
