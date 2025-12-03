<?php

declare(strict_types=1);

namespace Src\Models;

class AiToken
{
    public ?int $id = null;
    public string $name = '';
    public string $tokenHash = '';
    public bool $isActive = true;
}
