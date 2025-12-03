<?php

declare(strict_types=1);

namespace Src;

class Router
{
    private array $routes = [];
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        $routeFile = dirname(__DIR__) . '/config/routes.php';
        if (!file_exists($routeFile)) {
            throw new \RuntimeException('Routes file not found.');
        }

        $this->routes = require $routeFile;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            [$routeMethod, $routePath, $handler] = $route;

            if (strtoupper($routeMethod) !== strtoupper($method)) {
                continue;
            }

            $params = $this->match($routePath, $path);
            if ($params === null) {
                continue;
            }

            [$controllerClass, $action] = $handler;
            $controller = new $controllerClass($this->config);
            $controller->$action($params);
            return;
        }

        http_response_code(404);
        echo 'Not Found';
    }

    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }

        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts = explode('/', trim($path, '/'));

        if (count($patternParts) !== count($pathParts)) {
            return null;
        }

        $params = [];

        foreach ($patternParts as $i => $part) {
            if (preg_match('/^{(.+)}$/', $part, $matches)) {
                $params[$matches[1]] = $pathParts[$i];
                continue;
            }

            if ($part !== $pathParts[$i]) {
                return null;
            }
        }

        return $params;
    }
}
