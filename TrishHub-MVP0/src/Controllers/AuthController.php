<?php

declare(strict_types=1);

namespace Src\Controllers;

use Src\Services\AuthService;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->authService = new AuthService($config);
    }

    public function showLogin(array $params = []): void
    {
        $this->view('auth/login');
    }

    public function login(array $params = []): void
    {
        // TODO: Implement real login logic in MVP1.
        $this->redirect('/repos');
    }

    public function logout(array $params = []): void
    {
        // TODO: Implement session cleanup in MVP1.
        $this->redirect('/login');
    }
}
