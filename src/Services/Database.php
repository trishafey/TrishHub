<?php

declare(strict_types=1);

namespace Src\Services;

use PDO;

class Database
{
    private array $config;
    private ?PDO $connection = null;

    public function __construct(array $config)
    {
        $this->config = $config['db'] ?? [];
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'] ?? '127.0.0.1',
                $this->config['port'] ?? 3306,
                $this->config['database'] ?? '',
                $this->config['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->connection = new PDO(
                $dsn,
                $this->config['username'] ?? '',
                $this->config['password'] ?? '',
                $options
            );
        }

        return $this->connection;
    }

    /**
     * Run a raw SQL statement (no results returned).
     */
    public function runSql(string $sql): void
    {
        $this->getConnection()->exec($sql);
    }

    /**
     * Run all .sql migration files in the given directory.
     *
     * Keeps track of applied files in a simple migrations table so
     * subsequent runs skip already-applied migrations.
     *
     * @return array{applied: string[], skipped: string[]}
     */
    public function runMigrations(string $migrationsDir): array
    {
        $pdo = $this->getConnection();

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $applied = [];
        $skipped = [];

        $dir = rtrim($migrationsDir, '/');
        if (!is_dir($dir)) {
            return ['applied' => $applied, 'skipped' => $skipped];
        }

        $files = glob($dir . '/*.sql') ?: [];
        sort($files);

        $selectStmt = $pdo->prepare('SELECT 1 FROM migrations WHERE filename = :filename');
        $insertStmt = $pdo->prepare('INSERT INTO migrations (filename) VALUES (:filename)');

        foreach ($files as $file) {
            $filename = basename($file);

            $selectStmt->execute(['filename' => $filename]);
            if ($selectStmt->fetchColumn()) {
                $skipped[] = $filename;
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            $pdo->exec($sql);
            $insertStmt->execute(['filename' => $filename]);
            $applied[] = $filename;
        }

        return ['applied' => $applied, 'skipped' => $skipped];
    }
}
