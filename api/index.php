<?php

/**
 * Vercel Serverless Entrypoint for Laravel
 * Handles /tmp storage setup and bootstraps Laravel directly.
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('LARAVEL_START', microtime(true));

if (($_GET['__health'] ?? null) === '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'php' => PHP_VERSION,
        'autoload' => file_exists(__DIR__ . '/../vendor/autoload.php'),
        'database' => file_exists(__DIR__ . '/../database/database.sqlite'),
        'sqlite' => extension_loaded('pdo_sqlite'),
    ]);
    exit;
}

// Register the Composer autoloader from vendor (installed by vercel-php builder)
require __DIR__ . '/../vendor/autoload.php';

// Ensure required /tmp directories exist (Vercel has read-only filesystem)
$vercelTmpDirs = [
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/app/public',
    '/tmp/storage/logs',
];
foreach ($vercelTmpDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// If using SQLite, copy the database to /tmp to make it writable
$dbConnection = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? ($_SERVER['DB_CONNECTION'] ?? 'sqlite'));
if ($dbConnection === 'sqlite') {
    $srcPath = __DIR__ . '/../database/database.sqlite';
    $sourceVersion = file_exists($srcPath) ? sha1_file($srcPath) : null;
    $dbPath = '/tmp/database-' . ($sourceVersion ?: 'default') . '.sqlite';

    // Each bundled database version gets its own writable serverless copy.
    if ($sourceVersion && !file_exists($dbPath)) {
        copy($srcPath, $dbPath);
        @chmod($dbPath, 0666);
    }
    putenv('DB_DATABASE=' . $dbPath);
    $_ENV['DB_DATABASE'] = $dbPath;
    $_SERVER['DB_DATABASE'] = $dbPath;
}


use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

try {
    // Bootstrap Laravel application
    /** @var Application $app */
    $app = require_once __DIR__ . '/../bootstrap/app.php';

    // Override storage path to /tmp for Vercel serverless
    $app->useStoragePath('/tmp/storage');

    // Handle the HTTP request
    $app->handleRequest(Request::capture());
} catch (Throwable $exception) {
    if (($_GET['__diagnose'] ?? null) === '1') {
        header('Content-Type: application/json', true, 500);
        echo json_encode([
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);
        exit;
    }

    throw $exception;
}
