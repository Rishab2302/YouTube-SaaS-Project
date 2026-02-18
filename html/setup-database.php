<?php

// Database setup script - creates database and runs all migrations
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "TaskFlow Database Setup\n";
echo "======================\n";

try {
    // Connect to MySQL server (without specifying database)
    $host = $_ENV['DB_HOST'];
    $port = $_ENV['DB_PORT'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $database = $_ENV['DB_DATABASE'];

    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);

    echo "✓ Connected to MySQL server\n";

    // Create database if it doesn't exist
    echo "Creating database '$database'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$database' created/verified\n";

    // Switch to the database
    $pdo->exec("USE `$database`");
    echo "✓ Using database '$database'\n\n";

    // Create migrations table first
    echo "Setting up migrations tracking...\n";
    $migrationTableSql = "
        CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($migrationTableSql);
    echo "✓ Migrations table created\n\n";

    // Get all migration files
    $migrationFiles = glob(__DIR__ . '/migrations/*.sql');
    sort($migrationFiles);

    // Get already executed migrations
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Running database migrations...\n";
    echo "===============================\n";

    foreach ($migrationFiles as $filePath) {
        $filename = basename($filePath);

        // Skip if not a numbered migration file
        if (!preg_match('/^\d{3}_.*\.sql$/', $filename)) {
            continue;
        }

        if (in_array($filename, $executed)) {
            echo "⏭  Skipping $filename (already executed)\n";
            continue;
        }

        echo "🔄 Running $filename... ";

        try {
            $sql = file_get_contents($filePath);
            if ($sql === false) {
                throw new Exception("Could not read migration file");
            }

            // Execute the migration
            $pdo->beginTransaction();
            $pdo->exec($sql);

            // Record the migration as completed
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$filename]);

            $pdo->commit();
            echo "✅ Success\n";

        } catch (Exception $e) {
            $pdo->rollBack();
            echo "❌ Failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    echo "\n🎉 Database setup completed successfully!\n\n";

    // Show created tables
    echo "Created tables:\n";
    echo "===============\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // Get row count for each table
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $countStmt->fetch()['count'];
        echo "📋 $table ($count rows)\n";
    }

    echo "\n✅ TaskFlow database is ready!\n";
    echo "You can now run the application at: " . $_ENV['APP_URL'] . "\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Ensure MySQL is running on port 8889\n";
    echo "2. Verify credentials: username 'vibe_templates', password 'Rishab123#'\n";
    echo "3. Check user has CREATE DATABASE privileges\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Setup error: " . $e->getMessage() . "\n";
    exit(1);
}