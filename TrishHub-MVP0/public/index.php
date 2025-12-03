<?php

declare(strict_types=1);

use Src\Router;

require dirname(__DIR__) . '/src/bootstrap.php';

$config = $GLOBALS['config'] ?? [];
$router = new Router($config);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
