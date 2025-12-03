<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'Src\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

$GLOBALS['config'] = require dirname(__DIR__) . '/config/config.php';

session_start();
