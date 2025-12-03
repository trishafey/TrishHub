<?php

declare(strict_types=1);

namespace Src\Models;

class AiTask
{
    public ?int $id = null;
    public int $repositoryId;
    public ?int $createdBy = null;
    public ?int $tokenId = null;
    public string $branchName = '';
    public array $paths = [];
    public string $instruction = '';
    public string $status = 'logged_only';
    public ?string $resultSummary = null;
}
