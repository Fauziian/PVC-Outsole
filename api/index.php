<?php

/**
 * Vercel Serverless Entrypoint for Laravel
 * Handles /tmp storage setup and bootstraps Laravel directly.
 */

define('LARAVEL_START', microtime(true));

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
    $dbPath = '/tmp/database.sqlite';
    $srcPath = __DIR__ . '/../database/database.sqlite';
    if (!file_exists($dbPath) && file_exists($srcPath)) {
        copy($srcPath, $dbPath);
        @chmod($dbPath, 0666);
    }
    putenv('DB_DATABASE=' . $dbPath);
    $_ENV['DB_DATABASE'] = $dbPath;
    $_SERVER['DB_DATABASE'] = $dbPath;
}


use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel application
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Override storage path to /tmp for Vercel serverless
$app->useStoragePath('/tmp/storage');

// Handle the HTTP request
$app->handleRequest(Request::capture());
