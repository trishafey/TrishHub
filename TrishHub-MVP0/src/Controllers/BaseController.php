<?php

declare(strict_types=1);

namespace Src\Controllers;

abstract class BaseController
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function view(string $template, array $data = []): void
    {
        $viewsPath = $this->config['paths']['views'] ?? '';
        $layout = $viewsPath . '/layout.php';
        $viewFile = $viewsPath . '/' . $template . '.php';

        extract($data, EXTR_SKIP);

        if (file_exists($layout)) {
            include $layout;
        } elseif (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "View not found: {$template}";
        }
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
