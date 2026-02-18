#!/usr/bin/env php
<?php

// Load environment and dependencies
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsPath;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->migrationsPath = __DIR__;
        $this->createMigrationsTable();
    }

    public function run(): void
    {
        echo "Starting database migrations...\n";

        try {
            $migrations = $this->getPendingMigrations();

            if (empty($migrations)) {
                echo "No pending migrations found.\n";
                return;
            }

            foreach ($migrations as $migration) {
                $this->runMigration($migration);
            }

            echo "All migrations completed successfully!\n";

        } catch (Exception $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $this->pdo->exec($sql);
    }

    private function getPendingMigrations(): array
    {
        // Get all migration files
        $files = glob($this->migrationsPath . '/*.sql');
        $migrations = [];

        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/^\d{3}_.*\.sql$/', $filename)) {
                $migrations[] = $filename;
            }
        }

        sort($migrations);

        // Get already executed migrations
        $stmt = $this->pdo->query("SELECT migration FROM migrations");
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Return pending migrations
        return array_diff($migrations, $executed);
    }

    private function runMigration(string $migration): void
    {
        echo "Running migration: {$migration}... ";

        $filePath = $this->migrationsPath . '/' . $migration;
        $sql = file_get_contents($filePath);

        if ($sql === false) {
            throw new Exception("Could not read migration file: {$migration}");
        }

        try {
            // Execute the migration
            $this->pdo->beginTransaction();
            $this->pdo->exec($sql);

            // Record the migration as completed
            $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migration]);

            $this->pdo->commit();
            echo "✓ Completed\n";

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Failed to execute migration {$migration}: " . $e->getMessage());
        }
    }

    public function status(): void
    {
        echo "Migration Status:\n";
        echo "================\n";

        $allMigrations = glob($this->migrationsPath . '/*.sql');
        sort($allMigrations);

        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY migration");
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($allMigrations as $file) {
            $filename = basename($file);
            if (preg_match('/^\d{3}_.*\.sql$/', $filename)) {
                $status = in_array($filename, $executed) ? '✓ Executed' : '✗ Pending';
                echo sprintf("%-50s %s\n", $filename, $status);
            }
        }
    }

    public function testConnection(): void
    {
        try {
            $this->pdo->query('SELECT 1');
            echo "✓ Database connection successful!\n";
        } catch (Exception $e) {
            echo "✗ Database connection failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

// CLI Interface
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

$command = $argv[1] ?? 'migrate';

try {
    $runner = new MigrationRunner();

    switch ($command) {
        case 'migrate':
            $runner->run();
            break;
        case 'status':
            $runner->status();
            break;
        case 'test':
            $runner->testConnection();
            break;
        default:
            echo "Usage: php migrate.php [command]\n";
            echo "Commands:\n";
            echo "  migrate  - Run pending migrations (default)\n";
            echo "  status   - Show migration status\n";
            echo "  test     - Test database connection\n";
            exit(1);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}