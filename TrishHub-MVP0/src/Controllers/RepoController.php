<?php

declare(strict_types=1);

namespace Src\Controllers;

use Src\Services\GitService;

class RepoController extends BaseController
{
    private GitService $gitService;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->gitService = new GitService($config);
    }

    public function index(array $params = []): void
    {
        $repos = [];
        $this->view('repos/index', ['repos' => $repos]);
    }

    public function create(array $params = []): void
    {
        $this->view('repos/create');
    }

    public function store(array $params = []): void
    {
        // TODO: Create repo row + git init in MVP1.
        $this->redirect('/repos');
    }

    public function show(array $params): void
    {
        $name = $params['name'] ?? null;
        $this->view('repos/show', ['name' => $name]);
    }

    public function tree(array $params): void
    {
        echo 'Tree view (placeholder)';
    }

    public function file(array $params): void
    {
        echo 'File view (placeholder)';
    }

    public function commits(array $params): void
    {
        echo 'Commits view (placeholder)';
    }
}
