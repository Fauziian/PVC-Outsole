<?php

// Redirect storage to /tmp for Vercel serverless (read-only filesystem)
$app = require __DIR__ . '/../bootstrap/app.php';

// Override storage paths to use /tmp (the only writable directory on Vercel)
$app->useStoragePath('/tmp/storage');

// Also ensure the necessary /tmp directories exist
$dirs = [
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/app',
    '/tmp/storage/logs',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
