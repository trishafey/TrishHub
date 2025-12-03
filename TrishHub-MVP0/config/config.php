<?php

$baseDir = dirname(__DIR__);

$envFile = $baseDir . '/config/env.local.php';
if (!file_exists($envFile)) {
    throw new RuntimeException('Missing config/env.local.php. Copy env.local.php.example and adjust.');
}

$env = require $envFile;

return [
    'db'    => $env['db'],
    'app'   => $env['app'],
    'paths' => $env['paths'] + [
        'base_dir' => $baseDir,
        'views'    => $baseDir . '/src/Views',
    ],
];
