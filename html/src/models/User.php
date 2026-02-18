<?php

namespace App\Models;

use Database;
use PDO;
use Exception;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find user by email address
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, first_name, last_name, email, password_hash, email_verified_at,
                       verification_token, verification_expires_at, theme_preference, timezone,
                       created_at, updated_at
                FROM users
                WHERE email = ? AND deleted_at IS NULL
            ');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (Exception $e) {
            error_log("User::findByEmail error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find user by ID
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, first_name, last_name, email, password_hash, email_verified_at,
                       verification_token, verification_expires_at, theme_preference, timezone,
                       created_at, updated_at
                FROM users
                WHERE id = ? AND deleted_at IS NULL
            ');
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (Exception $e) {
            error_log("User::findById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new user
     * REQ-AUTH-004: Uses password_hash() with PASSWORD_ARGON2ID (fallback to PASSWORD_BCRYPT)
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function create(array $data): ?int
    {
        try {
            // Use PASSWORD_ARGON2ID if available, fallback to PASSWORD_BCRYPT
            $passwordAlgo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
            $passwordHash = password_hash($data['password'], $passwordAlgo);

            $stmt = $this->db->prepare('
                INSERT INTO users (
                    first_name, last_name, email, password_hash,
                    theme_preference, timezone, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ');

            $result = $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $passwordHash,
                $data['theme_preference'] ?? 'auto',
                $data['timezone'] ?? 'UTC'
            ]);

            if ($result) {
                return (int) $this->db->lastInsertId();
            }

            return null;
        } catch (Exception $e) {
            error_log("User::create error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Set email_verified_at to now
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function verifyEmail(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE users
                SET email_verified_at = NOW(),
                    verification_token = NULL,
                    verification_expires_at = NULL,
                    updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ');

            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("User::verifyEmail error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find user by hashed verification token
     * REQ-SEC-008: Stores only hashed tokens in database
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function findByVerificationToken(string $tokenHash): ?array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, first_name, last_name, email, email_verified_at,
                       verification_token, verification_expires_at
                FROM users
                WHERE verification_token = ?
                AND verification_expires_at > NOW()
                AND email_verified_at IS NULL
                AND deleted_at IS NULL
            ');
            $stmt->execute([$tokenHash]);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (Exception $e) {
            error_log("User::findByVerificationToken error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update verification token for user
     * REQ-AUTH-005: Tokens expire after 24 hours
     * REQ-SEC-008: Store hashed token in database
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function updateVerificationToken(int $userId, string $tokenHash, string $expiresAt): bool
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE users
                SET verification_token = ?,
                    verification_expires_at = ?,
                    updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ');

            return $stmt->execute([$tokenHash, $expiresAt, $userId]);
        } catch (Exception $e) {
            error_log("User::updateVerificationToken error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email exists (for validation)
     * REQ-AUTH-002: Email must be unique across system
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        try {
            $sql = 'SELECT COUNT(*) FROM users WHERE email = ? AND deleted_at IS NULL';
            $params = [$email];

            if ($excludeUserId !== null) {
                $sql .= ' AND id != ?';
                $params[] = $excludeUserId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("User::emailExists error: " . $e->getMessage());
            return true; // Err on the side of caution
        }
    }

    /**
     * Update user profile data
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function updateProfile(int $userId, array $data): bool
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE users
                SET first_name = ?,
                    last_name = ?,
                    email = ?,
                    theme_preference = ?,
                    timezone = ?,
                    updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ');

            return $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['theme_preference'] ?? 'auto',
                $data['timezone'] ?? 'UTC',
                $userId
            ]);
        } catch (Exception $e) {
            error_log("User::updateProfile error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user password
     * REQ-AUTH-004: Uses password_hash() with strong algorithm
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        try {
            $passwordAlgo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
            $passwordHash = password_hash($newPassword, $passwordAlgo);

            $stmt = $this->db->prepare('
                UPDATE users
                SET password_hash = ?, updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ');

            return $stmt->execute([$passwordHash, $userId]);
        } catch (Exception $e) {
            error_log("User::updatePassword error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Soft delete user account
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function deleteAccount(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare('
                UPDATE users
                SET deleted_at = NOW(), updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ');

            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("User::deleteAccount error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user statistics
     * REQ-SEC-009: Scoped by user_id
     * REQ-SEC-001: Uses PDO prepared statements
     */
    public function getStats(int $userId): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN due_date < NOW() AND status != "completed" THEN 1 ELSE 0 END) as overdue_tasks
                FROM tasks
                WHERE user_id = ? AND deleted_at IS NULL
            ');
            $stmt->execute([$userId]);
            $stats = $stmt->fetch();

            return $stats ?: [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'in_progress_tasks' => 0,
                'overdue_tasks' => 0
            ];
        } catch (Exception $e) {
            error_log("User::getStats error: " . $e->getMessage());
            return [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'in_progress_tasks' => 0,
                'overdue_tasks' => 0
            ];
        }
    }
}