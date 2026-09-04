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
    '/tmp/bootstrap/cache',
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

// Laravel may rebuild these manifests at runtime; Vercel's project tree is read-only.
foreach ([
    'APP_SERVICES_CACHE' => '/tmp/bootstrap/cache/services.php',
    'APP_PACKAGES_CACHE' => '/tmp/bootstrap/cache/packages.php',
] as $key => $value) {
    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
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

    // Compatibility for the bundled demo database: the former "management"
    // account is now part of HR/Keuangan. Normal installations receive the
    // same change through the application migration.
    if (file_exists($dbPath)) {
        (new PDO('sqlite:' . $dbPath))->exec("UPDATE users SET role = 'hr' WHERE role = 'management'");
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

// Some serverless PHP workers report the app as bootstrapped before the
// framework bindings exist. Bootstrap the Laravel stack explicitly there.
if (! $app->bound('config') || ! $app->bound('view')) {
    $app->bootstrapWith([
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ]);
}

// Handle the HTTP request
$app->handleRequest(Request::capture());
