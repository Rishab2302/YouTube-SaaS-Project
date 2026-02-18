<?php

class Session
{
    /**
     * Store a flash message in the session
     * Flash messages are displayed once and then removed
     */
    public static function flash(string $key, string $message): void
    {
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Retrieve and remove a flash message from the session
     */
    public static function getFlash(string $key): ?string
    {
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);

            // Clean up empty flash array
            if (empty($_SESSION['flash'])) {
                unset($_SESSION['flash']);
            }

            return $message;
        }

        return null;
    }

    /**
     * Check if a flash message exists
     */
    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['flash'][$key]);
    }

    /**
     * Get all flash messages and remove them from session
     */
    public static function getAllFlashes(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }

    /**
     * Store old input data for form redisplay on validation errors
     */
    public static function flashInput(array $data): void
    {
        $_SESSION['old_input'] = $data;
    }

    /**
     * Get old input value
     */
    public static function getOldInput(string $key, string $default = ''): string
    {
        $value = $_SESSION['old_input'][$key] ?? $default;
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Clear old input data
     */
    public static function clearOldInput(): void
    {
        unset($_SESSION['old_input']);
    }

    /**
     * Store validation errors
     */
    public static function flashErrors(array $errors): void
    {
        $_SESSION['errors'] = $errors;
    }

    /**
     * Get validation errors
     */
    public static function getErrors(): array
    {
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);
        return $errors;
    }

    /**
     * Get errors for a specific field
     */
    public static function getFieldErrors(string $field): array
    {
        $errors = $_SESSION['errors'][$field] ?? [];
        return $errors;
    }

    /**
     * Check if there are errors for a specific field
     */
    public static function hasFieldErrors(string $field): bool
    {
        return !empty($_SESSION['errors'][$field] ?? []);
    }

    /**
     * Clear all validation errors
     */
    public static function clearErrors(): void
    {
        unset($_SESSION['errors']);
    }

    /**
     * Set the authenticated user ID
     * REQ-AUTH-011: Store user ID in session
     */
    public static function setUserId(int $userId): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['login_time'] = time();

        // Regenerate session ID for security
        session_regenerate_id(true);
    }

    /**
     * Get the authenticated user ID
     */
    public static function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Clear user authentication data
     */
    public static function clearAuth(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['login_time']);
        session_regenerate_id(true);
    }

    /**
     * Get login time
     */
    public static function getLoginTime(): ?int
    {
        return $_SESSION['login_time'] ?? null;
    }

    /**
     * Store the intended URL for redirect after login
     */
    public static function setIntendedUrl(string $url): void
    {
        $_SESSION['intended_url'] = $url;
    }

    /**
     * Get and clear the intended URL
     */
    public static function getIntendedUrl(): ?string
    {
        $url = $_SESSION['intended_url'] ?? null;
        unset($_SESSION['intended_url']);
        return $url;
    }

    /**
     * Generate and store CSRF token
     * REQ-SEC-003: CSRF protection
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Get CSRF token
     */
    public static function getCsrfToken(): ?string
    {
        return $_SESSION['csrf_token'] ?? null;
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        if (!$sessionToken || !$token) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Regenerate CSRF token
     */
    public static function regenerateCsrfToken(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * Store data in session
     */
    public static function put(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get data from session
     */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Remove data from session
     */
    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the entire session
     */
    public static function destroy(): void
    {
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Check if session has a key
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
}