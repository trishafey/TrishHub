<?php

// TrishHub local environment config

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'trishhub',
        'username' => 'root',      // or your hosting DB user
        'password' => '',          // or the password from hosting
        'charset' => 'utf8mb4',
    ],

    'app' => [
        'base_url' => 'http://localhost',
        'debug' => true,
    ],

    'paths' => [
        'git_root' => __DIR__ . '/../repos',
        'log_dir'  => __DIR__ . '/../logs',
    ],
];