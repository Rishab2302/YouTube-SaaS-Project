<?php

// Database connection test script
// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load database config
require_once __DIR__ . '/config/database.php';

echo "Testing database connection...\n";
echo "================================\n";
echo "Host: " . $_ENV['DB_HOST'] . "\n";
echo "Port: " . $_ENV['DB_PORT'] . "\n";
echo "Database: " . $_ENV['DB_DATABASE'] . "\n";
echo "Username: " . $_ENV['DB_USERNAME'] . "\n";
echo "================================\n\n";

try {
    // Test connection
    $pdo = Database::getInstance();
    echo "✓ Database connection successful!\n";

    // Test basic query
    $stmt = $pdo->query('SELECT VERSION() as mysql_version');
    $result = $stmt->fetch();
    echo "✓ MySQL Version: " . $result['mysql_version'] . "\n";

    // Test database exists or create it
    $dbName = $_ENV['DB_DATABASE'];
    echo "\nChecking database '$dbName'...\n";

    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database '$dbName' exists\n";
    } else {
        echo "! Database '$dbName' does not exist. Creating...\n";
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database '$dbName' created successfully\n";

        // Reconnect to the new database
        unset($pdo);
        Database::$instance = null; // Reset singleton
        $pdo = Database::getInstance();
        echo "✓ Reconnected to database '$dbName'\n";
    }

    // Test table creation permissions
    echo "\nTesting table creation permissions...\n";
    $testTable = "test_permissions_" . time();
    $pdo->exec("CREATE TABLE `$testTable` (id INT AUTO_INCREMENT PRIMARY KEY, test VARCHAR(50))");
    echo "✓ Can create tables\n";

    $pdo->exec("DROP TABLE `$testTable`");
    echo "✓ Can drop tables\n";

    echo "\n🎉 Database connection and permissions verified!\n";
    echo "Ready to run migrations.\n";

} catch (Exception $e) {
    echo "❌ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure MySQL is running on port 8889\n";
    echo "2. Verify username 'vibe_templates' exists\n";
    echo "3. Check password is correct\n";
    echo "4. Ensure user has database creation privileges\n";
    exit(1);
}