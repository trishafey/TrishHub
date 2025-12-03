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
        if ($this->authService->check()) {
            $this->redirect('/repos');
        }

        $this->view('auth/login', [
            'error' => null,
            'old'   => ['username' => ''],
        ]);
    }

    public function login(array $params = []): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $this->view('auth/login', [
                'error' => 'Please enter both username/email and password.',
                'old'   => ['username' => $username],
            ]);
            return;
        }

        if ($this->authService->attempt($username, $password)) {
            $this->redirect('/repos');
            return;
        }

        $this->view('auth/login', [
            'error' => 'Invalid credentials.',
            'old'   => ['username' => $username],
        ]);
    }

    public function logout(array $params = []): void
    {
        $this->authService->logout();
        $this->redirect('/login');
    }

    public function showSignup(array $params = []): void
    {
        if ($this->authService->check()) {
            $this->redirect('/repos');
        }

        $this->view('auth/signup', [
            'errors' => [],
            'old'    => [
                'email'        => '',
                'username'     => '',
                'display_name' => '',
            ],
        ]);
    }

    public function signup(array $params = []): void
    {
        $email        = trim($_POST['email'] ?? '');
        $username     = trim($_POST['username'] ?? '');
        $displayName  = trim($_POST['display_name'] ?? '');
        $password     = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';

        $errors = [];

        if ($email === '') {
            $errors[] = 'Email is required.';
        }
        if ($password === '') {
            $errors[] = 'Password is required.';
        }
        if ($password !== '' && $password !== $confirmation) {
            $errors[] = 'Passwords do not match.';
        }

        if ($errors) {
            $this->view('auth/signup', [
                'errors' => $errors,
                'old'    => [
                    'email'        => $email,
                    'username'     => $username,
                    'display_name' => $displayName,
                ],
            ]);
            return;
        }

        $displayName = $displayName !== '' ? $displayName : ($username !== '' ? $username : $email);

        $created = $this->authService->register($email, $username, $password, $displayName, 'admin');

        if (!$created) {
            $this->view('auth/signup', [
                'errors' => ['Unable to create account. This email or username may already be in use.'],
                'old'    => [
                    'email'        => $email,
                    'username'     => $username,
                    'display_name' => $displayName,
                ],
            ]);
            return;
        }

        $this->authService->attempt($email, $password);
        $this->redirect('/repos');
    }
}
