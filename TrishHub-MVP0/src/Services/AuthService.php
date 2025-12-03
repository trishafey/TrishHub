<?php

declare(strict_types=1);

namespace Src\Services;

class AuthService
{
    private array $config;
    private Database $db;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->db = new Database($config);
    }

    public function attempt(string $username, string $password): bool
    {
        // TODO: Implement real logic in MVP1.
        return true;
    }

    public function logout(): void
    {
        // TODO: Implement session cleanup in MVP1.
    }

    public function check(): bool
    {
        // TODO: Implement real auth checks in MVP1.
        return true;
    }
}
