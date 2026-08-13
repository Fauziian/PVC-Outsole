<?php

/**
 * Vercel Serverless Entrypoint for Laravel
 * Ensures writable /tmp directories exist, then delegates to Laravel.
 */

// Ensure required /tmp directories exist for Vercel serverless (read-only filesystem)
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

// Forward all requests to Laravel's public/index.php entry point
require __DIR__ . '/../public/index.php';
