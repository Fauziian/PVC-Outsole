<?php

// Ensure required /tmp directories exist for Vercel serverless
$dirs = [
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/app/public',
    '/tmp/storage/logs',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Forward all requests to Laravel's public/index.php entry point
require __DIR__ . '/../public/index.php';
