<?php

declare(strict_types=1);

namespace Src\Controllers;

use Src\Services\Database;

class InstallController extends BaseController
{
    /**
     * Run all SQL migrations in /database/migrations and show a simple summary.
     *
     * Intended as a one-off setup endpoint: GET /install
     */
    public function run(array $params = []): void
    {
        $db = new Database($this->config);

        $baseDir = $this->config['paths']['base_dir'] ?? dirname(__DIR__, 2);
        $migrationsDir = $baseDir . '/database/migrations';

        $results = [
            'applied' => [],
            'skipped' => [],
        ];

        if (is_dir($migrationsDir)) {
            $results = $db->runMigrations($migrationsDir);
        }

        $this->view('install/result', [
            'results'       => $results,
            'migrationsDir' => $migrationsDir,
        ]);
    }
}
