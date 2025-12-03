<?php

declare(strict_types=1);

namespace Src\Models;

class Repository
{
    public ?int $id = null;
    public int $ownerUserId;
    public string $name = '';
    public string $slug = '';
    public ?string $description = null;
    public string $defaultBranch = 'main';
    public string $gitPath = '';
}
