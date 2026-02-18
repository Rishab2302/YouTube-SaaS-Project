<?php

class CsrfToken
{
    private const SESSION_KEY = '_csrf_token';
    private const TOKEN_LENGTH = 32;

    /**
     * Generate a new CSRF token and store it in the session
     */
    public static function generate(): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::SESSION_KEY] = $token;
        return $token;
    }

    /**
     * Get the current CSRF token from session, generate one if it doesn't exist
     */
    public static function get(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || empty($_SESSION[self::SESSION_KEY])) {
            return self::generate();
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Validate a CSRF token against the session token
     */
    public static function validate(string $token): bool
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * Generate a hidden input field for forms
     */
    public static function field(): string
    {
        $token = self::get();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Generate a meta tag for HTMX/AJAX requests
     */
    public static function metaTag(): string
    {
        $token = self::get();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Refresh the CSRF token (useful after login/logout)
     */
    public static function refresh(): string
    {
        return self::generate();
    }

    /**
     * Clear the CSRF token from session
     */
    public static function clear(): void
    {
        if (isset($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }

    /**
     * Verify and consume a token (one-time use)
     * Useful for highly sensitive operations
     */
    public static function verifyAndConsume(string $token): bool
    {
        $isValid = self::validate($token);

        if ($isValid) {
            // Generate a new token after successful verification
            self::generate();
        }

        return $isValid;
    }

    /**
     * Get token for HTMX headers
     */
    public static function getForHeaders(): array
    {
        return [
            'X-CSRF-Token' => self::get()
        ];
    }
}