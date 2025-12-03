<?php

declare(strict_types=1);

namespace Src\Models;

class User
{
    public ?int $id = null;
    public string $email = '';
    public ?string $username = null;
    public string $passwordHash = '';
    public string $displayName = '';
    public string $role = 'admin';
    public bool $isActive = true;
}
