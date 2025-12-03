<?php

declare(strict_types=1);

namespace Src\Services;

class GitService
{
    private string $gitRoot;

    public function __construct(array $config)
    {
        $this->gitRoot = rtrim($config['paths']['git_root'] ?? '/srv/trishhub/repos', '/');
    }

    public function getRepositoryPath(string $slug): string
    {
        return $this->gitRoot . '/' . $slug . '.git';
    }

    public function initRepository(string $slug): void
    {
        $repoPath = $this->getRepositoryPath($slug);

        if (!is_dir($repoPath)) {
            $cmd = sprintf('git init --bare %s', escapeshellarg($repoPath));
            shell_exec($cmd);
        }
    }
}
