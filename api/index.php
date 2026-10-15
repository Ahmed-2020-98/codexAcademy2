<?php

// This router lives under /api, but Laravel is mounted at the site root.
// Otherwise Symfony strips /api from API requests as the script base URL.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// Vercel's deployment bundle is read-only; only runtime caches belong in /tmp.
$runtime = sys_get_temp_dir().'/codex-academy';

foreach (['storage/framework/views', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/logs', 'bootstrap/cache'] as $directory) {
    $path = $runtime.'/'.$directory;

    if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
        throw new RuntimeException('Unable to initialize runtime storage.');
    }
}

$paths = [
    'LARAVEL_STORAGE_PATH' => $runtime.'/storage',
    'VIEW_COMPILED_PATH' => $runtime.'/storage/framework/views',
    'APP_PACKAGES_CACHE' => $runtime.'/bootstrap/cache/packages.php',
    'APP_SERVICES_CACHE' => $runtime.'/bootstrap/cache/services.php',
    'APP_CONFIG_CACHE' => $runtime.'/bootstrap/cache/config.php',
    'APP_ROUTES_CACHE' => $runtime.'/bootstrap/cache/routes.php',
    'APP_EVENTS_CACHE' => $runtime.'/bootstrap/cache/events.php',
];

foreach ($paths as $name => $path) {
    $_ENV[$name] = $_SERVER[$name] = $path;
    putenv($name.'='.$path);
}

require __DIR__.'/../public/index.php';
