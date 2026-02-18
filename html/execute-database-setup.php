<?php

// Execute database setup directly using PHP PDO
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "TaskFlow Database Setup via PHP\n";
echo "===============================\n\n";

try {
    // Database connection parameters
    $host = '127.0.0.1';
    $port = '8889';
    $username = 'vibe_templates';
    $password = 'Rishab123#';
    $database = 'taskflow';

    echo "Connecting to MySQL server...\n";

    // Connect without specifying database first
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);

    echo "✓ Connected to MySQL server\n";

    // Create database
    echo "\nCreating database '$database'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$database' created/exists\n";

    // Switch to database
    $pdo->exec("USE `$database`");
    echo "✓ Using database '$database'\n";

    // Read and execute the complete setup SQL
    echo "\nExecuting table creation scripts...\n";
    $setupSQL = file_get_contents(__DIR__ . '/taskflow_complete_setup.sql');

    if ($setupSQL === false) {
        throw new Exception("Could not read setup SQL file");
    }

    // Remove the database creation parts since we already did that
    $setupSQL = preg_replace('/CREATE DATABASE.*?;/i', '', $setupSQL);
    $setupSQL = preg_replace('/USE taskflow;/i', '', $setupSQL);

    // Split into individual statements and execute
    $statements = array_filter(array_map('trim', explode(';', $setupSQL)));

    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }

        try {
            $pdo->exec($statement);
            if (preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "⚠ Warning executing statement: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n🎉 Database setup completed!\n\n";

    // Show created tables
    echo "Verifying tables...\n";
    echo "===================\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $countStmt->fetch()['count'];
        echo "📋 $table ($count rows)\n";
    }

    echo "\nNow executing sample data insertion...\n";
    echo "=====================================\n";

    // Read and execute sample data
    $sampleSQL = file_get_contents(__DIR__ . '/insert_sample_data.sql');

    if ($sampleSQL !== false) {
        // Remove USE statement
        $sampleSQL = preg_replace('/USE taskflow;/i', '', $sampleSQL);

        // Split and execute statements
        $statements = array_filter(array_map('trim', explode(';', $sampleSQL)));

        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }

            try {
                $pdo->exec($statement);
                if (preg_match('/INSERT INTO\s+(\w+)/i', $statement, $matches)) {
                    echo "✓ Inserted data into: {$matches[1]}\n";
                }
            } catch (PDOException $e) {
                echo "⚠ Warning inserting data: " . $e->getMessage() . "\n";
            }
        }

        echo "\n✅ Sample data inserted!\n\n";

        // Show final summary
        echo "Final Database Summary:\n";
        echo "======================\n";
        foreach ($tables as $table) {
            $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $countStmt->fetch()['count'];
            echo "📋 $table ($count rows)\n";
        }

        // Show task status summary
        echo "\nTask Status Summary:\n";
        echo "===================\n";
        $statusStmt = $pdo->query("
            SELECT
                status,
                COUNT(*) as count
            FROM tasks
            WHERE deleted_at IS NULL
            GROUP BY status
            ORDER BY
                CASE status
                    WHEN 'backlog' THEN 1
                    WHEN 'todo' THEN 2
                    WHEN 'in_progress' THEN 3
                    WHEN 'review' THEN 4
                    WHEN 'done' THEN 5
                END
        ");

        $statusData = $statusStmt->fetchAll();
        foreach ($statusData as $row) {
            echo "📊 {$row['status']}: {$row['count']} tasks\n";
        }
    }

    echo "\n🎉 TaskFlow database is ready!\n";
    echo "Test user: john@example.com / password123\n";
    echo "Application URL: http://localhost:8000\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\nConnection details attempted:\n";
    echo "Host: $host\n";
    echo "Port: $port\n";
    echo "Username: $username\n";
    echo "Database: $database\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Setup error: " . $e->getMessage() . "\n";
    exit(1);
}