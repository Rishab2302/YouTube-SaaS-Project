#!/usr/bin/env python3

import mysql.connector
from mysql.connector import Error
import os
import sys

def main():
    print("TaskFlow Database Setup with Python")
    print("====================================")

    # Database connection parameters
    config = {
        'host': '127.0.0.1',
        'port': 8889,
        'user': 'vibe_templates',
        'password': 'Rishab123#',
        'charset': 'utf8mb4',
        'collation': 'utf8mb4_unicode_ci'
    }

    connection = None
    cursor = None

    try:
        # Connect to MySQL server (without specifying database)
        print(f"Connecting to MySQL server at {config['host']}:{config['port']}...")
        connection = mysql.connector.connect(**config)
        cursor = connection.cursor()

        print("✓ Connected to MySQL server")

        # Get MySQL version
        cursor.execute("SELECT VERSION()")
        version = cursor.fetchone()
        print(f"✓ MySQL Version: {version[0]}")

        # Create database
        database_name = 'taskflow'
        print(f"\nCreating database '{database_name}'...")
        cursor.execute(f"CREATE DATABASE IF NOT EXISTS {database_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
        print(f"✓ Database '{database_name}' created/exists")

        # Switch to the database
        cursor.execute(f"USE {database_name}")
        print(f"✓ Using database '{database_name}'")

        # Create migrations table
        print("\nSetting up migrations tracking...")
        migrations_table_sql = """
            CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """
        cursor.execute(migrations_table_sql)
        print("✓ Migrations table created")

        # Define all migration SQL
        migrations = [
            ('001_create_users_table.sql', '''
                CREATE TABLE users (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    email_verified_at TIMESTAMP NULL DEFAULT NULL,
                    verification_token VARCHAR(255) NULL DEFAULT NULL,
                    verification_expires_at TIMESTAMP NULL DEFAULT NULL,
                    theme_preference ENUM('light','dark','auto') NOT NULL DEFAULT 'auto',
                    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
                    deleted_at TIMESTAMP NULL DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                    INDEX idx_email (email),
                    INDEX idx_verification_token (verification_token),
                    INDEX idx_deleted_at (deleted_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            '''),
            ('002_create_categories_table.sql', '''
                CREATE TABLE categories (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    color VARCHAR(7) NOT NULL DEFAULT '#0d6efd',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                    INDEX idx_user_id (user_id),
                    UNIQUE KEY unique_user_category (user_id, name),

                    CONSTRAINT fk_categories_user_id
                        FOREIGN KEY (user_id)
                        REFERENCES users(id)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            '''),
            ('003_create_tasks_table.sql', '''
                CREATE TABLE tasks (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    category_id INT UNSIGNED NULL DEFAULT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NULL DEFAULT NULL,
                    status ENUM('backlog','todo','in_progress','review','done') NOT NULL DEFAULT 'todo',
                    priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
                    due_date DATE NULL DEFAULT NULL,
                    completed_at TIMESTAMP NULL DEFAULT NULL,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                    deleted_at TIMESTAMP NULL DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                    INDEX idx_user_id (user_id),
                    INDEX idx_category_id (category_id),
                    INDEX idx_due_date (due_date),
                    INDEX idx_status (status),
                    INDEX idx_priority (priority),
                    INDEX idx_deleted_at (deleted_at),
                    INDEX idx_user_status_deleted (user_id, status, deleted_at),
                    INDEX idx_user_due_date_deleted (user_id, due_date, deleted_at),

                    CONSTRAINT fk_tasks_user_id
                        FOREIGN KEY (user_id)
                        REFERENCES users(id)
                        ON DELETE CASCADE,
                    CONSTRAINT fk_tasks_category_id
                        FOREIGN KEY (category_id)
                        REFERENCES categories(id)
                        ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            '''),
            ('004_create_sub_tasks_table.sql', '''
                CREATE TABLE sub_tasks (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    task_id INT UNSIGNED NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    is_completed TINYINT(1) NOT NULL DEFAULT 0,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                    INDEX idx_task_id (task_id),
                    INDEX idx_sort_order (sort_order),

                    CONSTRAINT fk_sub_tasks_task_id
                        FOREIGN KEY (task_id)
                        REFERENCES tasks(id)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            '''),
            ('005_create_remember_tokens_table.sql', '''
                CREATE TABLE remember_tokens (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    token_hash VARCHAR(255) NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    INDEX idx_user_id (user_id),
                    INDEX idx_token_hash (token_hash),
                    INDEX idx_expires_at (expires_at),

                    CONSTRAINT fk_remember_tokens_user_id
                        FOREIGN KEY (user_id)
                        REFERENCES users(id)
                        ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            '''),
            ('006_create_password_resets_table.sql', '''
                CREATE TABLE password_resets (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    token_hash VARCHAR(255) NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    used_at TIMESTAMP NULL DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    INDEX idx_email (email),
                    INDEX idx_token_hash (token_hash),
                    INDEX idx_expires_at (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            '''),
            ('007_create_login_attempts_table.sql', '''
                CREATE TABLE login_attempts (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent VARCHAR(500) NULL DEFAULT NULL,
                    success TINYINT(1) NOT NULL,
                    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    INDEX idx_email (email),
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_success (success),
                    INDEX idx_attempted_at (attempted_at),
                    INDEX idx_email_attempted (email, attempted_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ''')
        ]

        # Check already executed migrations
        cursor.execute("SELECT migration FROM migrations")
        executed_migrations = set(row[0] for row in cursor.fetchall())

        # Execute pending migrations
        print("\nExecuting database migrations...")
        for migration_name, sql in migrations:
            if migration_name in executed_migrations:
                print(f"⏭  Skipping {migration_name} (already executed)")
                continue

            print(f"🔄 Running {migration_name}... ", end="")
            try:
                cursor.execute(sql)
                cursor.execute("INSERT INTO migrations (migration) VALUES (%s)", (migration_name,))
                connection.commit()
                print("✅ Success")
            except Error as e:
                print(f"❌ Failed: {e}")
                return False

        # Insert sample data
        print("\nInserting sample data...")

        # Insert test user (password is "password123" hashed with bcrypt)
        user_sql = '''
            INSERT IGNORE INTO users (first_name, last_name, email, password_hash, email_verified_at, theme_preference, timezone)
            VALUES ('John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'auto', 'UTC')
        '''
        cursor.execute(user_sql)

        # Get user ID
        cursor.execute("SELECT id FROM users WHERE email = 'john@example.com'")
        user_id = cursor.fetchone()[0]
        print(f"✓ Test user created with ID: {user_id}")

        # Insert default categories
        categories = [
            ('Work', '#0d6efd'),
            ('Personal', '#198754'),
            ('Health', '#dc3545'),
            ('Finance', '#ffc107'),
            ('Learning', '#6f42c1')
        ]

        for name, color in categories:
            cursor.execute(
                "INSERT IGNORE INTO categories (user_id, name, color) VALUES (%s, %s, %s)",
                (user_id, name, color)
            )
        print("✓ Default categories created")

        # Get category IDs
        cursor.execute("SELECT id, name FROM categories WHERE user_id = %s", (user_id,))
        category_map = {name: cat_id for cat_id, name in cursor.fetchall()}

        # Insert sample tasks
        tasks = [
            (category_map['Work'], 'Complete project proposal', 'Finish the Q1 project proposal for client presentation', 'in_progress', 'high', 'DATE_ADD(CURDATE(), INTERVAL 3 DAY)'),
            (category_map['Work'], 'Review team performance', 'Quarterly review of team members', 'todo', 'medium', 'DATE_ADD(CURDATE(), INTERVAL 7 DAY)'),
            (category_map['Personal'], 'Plan vacation', 'Research and book summer vacation', 'todo', 'medium', 'DATE_ADD(CURDATE(), INTERVAL 30 DAY)'),
            (category_map['Health'], 'Annual checkup', 'Schedule and attend annual medical checkup', 'todo', 'high', 'DATE_ADD(CURDATE(), INTERVAL 10 DAY)'),
            (category_map['Health'], 'Start exercise routine', 'Begin 3x weekly exercise program', 'in_progress', 'medium', 'NULL'),
            (None, 'Learn new skill', 'Start online course in machine learning', 'todo', 'medium', 'DATE_ADD(CURDATE(), INTERVAL 21 DAY)')
        ]

        for category_id, title, description, status, priority, due_date in tasks:
            sql = f'''
                INSERT INTO tasks (user_id, category_id, title, description, status, priority, due_date)
                VALUES (%s, %s, %s, %s, %s, %s, {due_date})
            '''
            cursor.execute(sql, (user_id, category_id, title, description, status, priority))

        # Insert completed tasks
        completed_tasks = [
            (category_map['Work'], 'Quarterly report', 'Complete and submit Q4 quarterly report', 'done', 'high'),
            (category_map['Personal'], 'Renew drivers license', 'Visit DMV to renew drivers license', 'done', 'medium')
        ]

        for category_id, title, description, status, priority in completed_tasks:
            cursor.execute('''
                INSERT INTO tasks (user_id, category_id, title, description, status, priority, due_date, completed_at)
                VALUES (%s, %s, %s, %s, %s, %s, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY))
            ''', (user_id, category_id, title, description, status, priority))

        print("✓ Sample tasks created")

        # Commit all changes
        connection.commit()

        # Show final status
        print("\n🎉 Database setup completed successfully!")
        print("\nDatabase Summary:")
        print("=================")

        cursor.execute("SHOW TABLES")
        tables = cursor.fetchall()

        for (table_name,) in tables:
            cursor.execute(f"SELECT COUNT(*) FROM {table_name}")
            count = cursor.fetchone()[0]
            print(f"📋 {table_name}: {count} rows")

        # Show task status summary
        print("\nTask Status Summary:")
        print("===================")
        cursor.execute('''
            SELECT status, COUNT(*) as count
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
        ''')

        for status, count in cursor.fetchall():
            print(f"📊 {status}: {count} tasks")

        print("\n✅ TaskFlow database is ready!")
        print("Test user: john@example.com / password123")

        return True

    except Error as e:
        print(f"❌ Database error: {e}")
        return False
    except Exception as e:
        print(f"❌ Unexpected error: {e}")
        return False
    finally:
        if cursor:
            cursor.close()
        if connection and connection.is_connected():
            connection.close()
            print("\n✓ Database connection closed")

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)