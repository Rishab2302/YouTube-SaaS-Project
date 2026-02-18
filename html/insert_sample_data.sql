-- TaskFlow Sample Data Insertion
-- Run this after the main database setup to add sample data

USE taskflow;

-- Insert a test user (password is "password123" hashed with bcrypt)
INSERT INTO users (first_name, last_name, email, password_hash, email_verified_at, theme_preference, timezone) VALUES
('John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), 'auto', 'UTC');

SET @user_id = LAST_INSERT_ID();

-- Insert default categories for the test user
INSERT INTO categories (user_id, name, color) VALUES
(@user_id, 'Work', '#0d6efd'),
(@user_id, 'Personal', '#198754'),
(@user_id, 'Health', '#dc3545'),
(@user_id, 'Finance', '#ffc107'),
(@user_id, 'Learning', '#6f42c1');

-- Get category IDs
SET @work_cat = (SELECT id FROM categories WHERE user_id = @user_id AND name = 'Work');
SET @personal_cat = (SELECT id FROM categories WHERE user_id = @user_id AND name = 'Personal');
SET @health_cat = (SELECT id FROM categories WHERE user_id = @user_id AND name = 'Health');

-- Insert sample tasks
INSERT INTO tasks (user_id, category_id, title, description, status, priority, due_date) VALUES
(@user_id, @work_cat, 'Complete project proposal', 'Finish the Q1 project proposal for client presentation', 'in_progress', 'high', DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
(@user_id, @work_cat, 'Review team performance', 'Quarterly review of team members', 'todo', 'medium', DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(@user_id, @work_cat, 'Update documentation', 'Update API documentation for version 2.0', 'backlog', 'low', DATE_ADD(CURDATE(), INTERVAL 14 DAY)),
(@user_id, @personal_cat, 'Plan vacation', 'Research and book summer vacation', 'todo', 'medium', DATE_ADD(CURDATE(), INTERVAL 30 DAY)),
(@user_id, @personal_cat, 'Organize garage', 'Clean and organize the garage storage', 'backlog', 'low', NULL),
(@user_id, @health_cat, 'Annual checkup', 'Schedule and attend annual medical checkup', 'todo', 'high', DATE_ADD(CURDATE(), INTERVAL 10 DAY)),
(@user_id, @health_cat, 'Start exercise routine', 'Begin 3x weekly exercise program', 'in_progress', 'medium', NULL),
(@user_id, NULL, 'Learn new skill', 'Start online course in machine learning', 'todo', 'medium', DATE_ADD(CURDATE(), INTERVAL 21 DAY));

-- Insert some completed tasks
INSERT INTO tasks (user_id, category_id, title, description, status, priority, due_date, completed_at) VALUES
(@user_id, @work_cat, 'Quarterly report', 'Complete and submit Q4 quarterly report', 'done', 'high', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@user_id, @personal_cat, 'Renew drivers license', 'Visit DMV to renew drivers license', 'done', 'medium', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Get some task IDs for sub-tasks
SET @task_proposal = (SELECT id FROM tasks WHERE user_id = @user_id AND title = 'Complete project proposal');
SET @task_exercise = (SELECT id FROM tasks WHERE user_id = @user_id AND title = 'Start exercise routine');

-- Insert sub-tasks
INSERT INTO sub_tasks (task_id, title, is_completed, sort_order) VALUES
(@task_proposal, 'Research market requirements', 1, 1),
(@task_proposal, 'Draft initial proposal', 1, 2),
(@task_proposal, 'Review with stakeholders', 0, 3),
(@task_proposal, 'Finalize and format document', 0, 4),
(@task_exercise, 'Buy gym membership', 1, 1),
(@task_exercise, 'Create workout schedule', 1, 2),
(@task_exercise, 'Complete first week', 0, 3);

-- Insert a soft-deleted task for testing trash functionality
INSERT INTO tasks (user_id, category_id, title, description, status, priority, deleted_at) VALUES
(@user_id, @work_cat, 'Old project task', 'This task was deleted', 'todo', 'low', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Show summary of inserted data
SELECT 'DATA INSERTION SUMMARY' as '';

SELECT
    'Users' as 'Table',
    COUNT(*) as 'Records'
FROM users
UNION ALL
SELECT
    'Categories' as 'Table',
    COUNT(*) as 'Records'
FROM categories
UNION ALL
SELECT
    'Tasks (Active)' as 'Table',
    COUNT(*) as 'Records'
FROM tasks
WHERE deleted_at IS NULL
UNION ALL
SELECT
    'Tasks (Deleted)' as 'Table',
    COUNT(*) as 'Records'
FROM tasks
WHERE deleted_at IS NOT NULL
UNION ALL
SELECT
    'Sub-tasks' as 'Table',
    COUNT(*) as 'Records'
FROM sub_tasks;

-- Show task summary by status
SELECT
    status,
    COUNT(*) as count,
    CONCAT(ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tasks WHERE deleted_at IS NULL), 1), '%') as percentage
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
    END;