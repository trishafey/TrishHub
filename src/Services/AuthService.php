<?php

declare(strict_types=1);

namespace Src\Services;

class AuthService
{
    private array $config;
    private Database $db;
    private ?array $user = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->db = new Database($config);
    }

    public function attempt(string $username, string $password): bool
    {
        $pdo = $this->db->getConnection();

        $stmt = $pdo->prepare(
            'SELECT * FROM users
             WHERE (email = :u OR username = :u)
               AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['u' => $username]);

        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }

        if (!password_verify($password, $row['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = (int) $row['id'];
        $this->user = $row;

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function user(): ?array
    {
        if ($this->user !== null) {
            return $this->user;
        }

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $this->user = $row;

        return $row;
    }

    public function register(
        string $email,
        ?string $username,
        string $password,
        string $displayName,
        string $role = 'admin'
    ): bool {
        $pdo = $this->db->getConnection();

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (email, username, password_hash, display_name, role, is_active)
             VALUES (:email, :username, :password_hash, :display_name, :role, 1)'
        );

        return $stmt->execute([
            'email'         => $email,
            'username'      => $username !== '' ? $username : null,
            'password_hash' => $hash,
            'display_name'  => $displayName,
            'role'          => $role,
        ]);
    }
}
