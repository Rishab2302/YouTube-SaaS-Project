<?php

namespace App\Models;

use Database;
use PDO;
use Exception;

class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create default categories for new user
     * REQ-CAT-008: 5 default categories - Work (blue), Personal (green), Health (red), Finance (yellow), Learning (purple)
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function createDefaultCategories(int $userId): bool
    {
        try {
            $defaultCategories = [
                ['name' => 'Work', 'color' => 'blue'],
                ['name' => 'Personal', 'color' => 'green'],
                ['name' => 'Health', 'color' => 'red'],
                ['name' => 'Finance', 'color' => 'yellow'],
                ['name' => 'Learning', 'color' => 'purple']
            ];

            $stmt = $this->db->prepare('
                INSERT INTO categories (user_id, name, color, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ');

            $this->db->beginTransaction();

            foreach ($defaultCategories as $category) {
                $result = $stmt->execute([
                    $userId,
                    $category['name'],
                    $category['color']
                ]);

                if (!$result) {
                    $this->db->rollBack();
                    return false;
                }
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Category::createDefaultCategories error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all categories for a user
     * REQ-SEC-009: Scoped by user_id
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function getUserCategories(int $userId): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, name, color, created_at, updated_at
                FROM categories
                WHERE user_id = ?
                ORDER BY name ASC
            ');
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Category::getUserCategories error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find category by ID (scoped to user)
     * REQ-SEC-009: Scoped by user_id
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function findById(int $categoryId, int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, name, color, created_at, updated_at
                FROM categories
                WHERE id = ? AND user_id = ?            ');
            $stmt->execute([$categoryId, $userId]);
            $category = $stmt->fetch();

            return $category ?: null;
        } catch (Exception $e) {
            error_log("Category::findById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new category
     * REQ-SEC-009: Scoped by user_id
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function create(int $userId, array $data): ?int
    {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO categories (user_id, name, color, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ');

            $result = $stmt->execute([
                $userId,
                $data['name'],
                $data['color'] ?? 'blue'
            ]);

            if ($result) {
                return (int) $this->db->lastInsertId();
            }

            return null;
        } catch (Exception $e) {
            error_log("Category::create error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update a category
     * REQ-SEC-009: Scoped by user_id
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function update(int $categoryId, int $userId, array $data): bool
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE categories
                SET name = ?, color = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?            ');

            return $stmt->execute([
                $data['name'],
                $data['color'] ?? 'blue',
                $categoryId,
                $userId
            ]);
        } catch (Exception $e) {
            error_log("Category::update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Soft delete a category
     * REQ-SEC-009: Scoped by user_id
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function delete(int $categoryId, int $userId): bool
    {
        try {
            // First, check if category is being used by any tasks
            $stmt = $this->db->prepare('
                SELECT COUNT(*) FROM tasks
                WHERE category_id = ? AND user_id = ?            ');
            $stmt->execute([$categoryId, $userId]);
            $taskCount = $stmt->fetchColumn();

            if ($taskCount > 0) {
                // Set tasks to no category before deleting the category
                $stmt = $this->db->prepare('
                    UPDATE tasks
                    SET category_id = NULL, updated_at = NOW()
                    WHERE category_id = ? AND user_id = ?                ');
                $stmt->execute([$categoryId, $userId]);
            }

            // Delete the category
            $stmt = $this->db->prepare('
                DELETE FROM categories
                WHERE id = ? AND user_id = ?
            ');

            return $stmt->execute([$categoryId, $userId]);
        } catch (Exception $e) {
            error_log("Category::delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get category usage statistics
     * REQ-SEC-009: Scoped by user_id
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function getCategoryStats(int $userId): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT
                    c.id,
                    c.name,
                    c.color,
                    COUNT(t.id) as task_count,
                    SUM(CASE WHEN t.status = "completed" THEN 1 ELSE 0 END) as completed_tasks
                FROM categories c
                LEFT JOIN tasks t ON c.id = t.category_id AND t.deleted_at IS NULL
                WHERE c.user_id = ? AND c.deleted_at IS NULL
                GROUP BY c.id, c.name, c.color
                ORDER BY c.name ASC
            ');
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Category::getCategoryStats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if category name exists for user (for validation)
     * REQ-SEC-009: Scoped by user_id
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function nameExists(int $userId, string $name, ?int $excludeCategoryId = null): bool
    {
        try {
            $sql = 'SELECT COUNT(*) FROM categories WHERE user_id = ? AND name = ? AND deleted_at IS NULL';
            $params = [$userId, $name];

            if ($excludeCategoryId !== null) {
                $sql .= ' AND id != ?';
                $params[] = $excludeCategoryId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Category::nameExists error: " . $e->getMessage());
            return true; // Err on the side of caution
        }
    }
}