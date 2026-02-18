<?php

class Middleware
{
    /**
     * Authentication middleware - checks for valid user session
     * Redirects to login if not authenticated, saving current URL for post-login redirect
     */
    public static function auth(): bool
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            // Save the current URL for redirect after login
            $currentUrl = $_SERVER['REQUEST_URI'];
            if ($currentUrl !== '/login' && $currentUrl !== '/logout') {
                $_SESSION['redirect_after_login'] = $currentUrl;
            }

            // If it's an HTMX request, return a redirect trigger
            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                header('HX-Redirect: /login');
                exit;
            }

            // Regular redirect
            header('Location: /login');
            exit;
        }

        return true; // User is authenticated, continue
    }

    /**
     * Guest middleware - ensures user is NOT authenticated
     * Used for login/register pages to redirect already logged-in users
     */
    public static function guest(): bool
    {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            // User is already logged in, redirect to dashboard

            // If it's an HTMX request, return a redirect trigger
            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                header('HX-Redirect: /dashboard');
                exit;
            }

            // Regular redirect
            header('Location: /dashboard');
            exit;
        }

        return true; // User is not authenticated, continue
    }

    /**
     * CSRF middleware - validates CSRF token for state-changing requests
     * Automatically applied to POST, PUT, PATCH, DELETE requests
     */
    public static function csrf(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Only validate CSRF for state-changing requests
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        // Get token from various sources
        $token = null;

        // Check header (for HTMX requests)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        // Check POST data
        elseif (isset($_POST['_token'])) {
            $token = $_POST['_token'];
        }
        // Check JSON payload for API requests
        elseif ($_SERVER['CONTENT_TYPE'] === 'application/json') {
            $json = json_decode(file_get_contents('php://input'), true);
            $token = $json['_token'] ?? null;
        }

        if (!$token || !CsrfToken::validate($token)) {
            // CSRF validation failed
            http_response_code(403);

            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                echo '<div class="alert alert-danger">Security token mismatch. Please refresh the page and try again.</div>';
            } else {
                echo '<!DOCTYPE html>
                <html>
                <head><title>403 Forbidden</title></head>
                <body>
                    <h1>403 - Forbidden</h1>
                    <p>Security token mismatch. Please refresh the page and try again.</p>
                    <a href="javascript:history.back()">Go Back</a>
                </body>
                </html>';
            }
            exit;
        }

        return true; // CSRF token is valid, continue
    }

    /**
     * Rate limiting middleware - prevents brute force attacks
     * Can be configured for different endpoints and time windows
     */
    public static function rateLimit(int $maxAttempts = 5, int $windowMinutes = 15): callable
    {
        return function() use ($maxAttempts, $windowMinutes): bool {
            $key = 'rate_limit_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $attempts = $_SESSION[$key] ?? [];
            $now = time();
            $windowStart = $now - ($windowMinutes * 60);

            // Remove old attempts outside the window
            $attempts = array_filter($attempts, fn($timestamp) => $timestamp > $windowStart);

            if (count($attempts) >= $maxAttempts) {
                http_response_code(429);

                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    echo '<div class="alert alert-warning">Too many attempts. Please try again in ' . $windowMinutes . ' minutes.</div>';
                } else {
                    echo '<!DOCTYPE html>
                    <html>
                    <head><title>429 Too Many Requests</title></head>
                    <body>
                        <h1>429 - Too Many Requests</h1>
                        <p>Too many attempts. Please try again in ' . $windowMinutes . ' minutes.</p>
                    </body>
                    </html>';
                }
                exit;
            }

            // Record this attempt
            $attempts[] = $now;
            $_SESSION[$key] = $attempts;

            return true;
        };
    }

    /**
     * Admin middleware - checks for admin privileges
     * Requires user to be authenticated AND have admin role
     */
    public static function admin(): bool
    {
        // First check if user is authenticated
        if (!self::auth()) {
            return false;
        }

        // Check if user has admin privileges
        // This would typically check a role field in the database
        // For now, we'll use a simple session check
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            http_response_code(403);

            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                echo '<div class="alert alert-danger">Access denied. Admin privileges required.</div>';
            } else {
                header('Location: /dashboard');
            }
            exit;
        }

        return true;
    }

    /**
     * JSON middleware - ensures request accepts JSON responses
     * Used for API endpoints
     */
    public static function json(): bool
    {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';

        if (strpos($acceptHeader, 'application/json') === false) {
            http_response_code(406);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'This endpoint only accepts JSON requests']);
            exit;
        }

        // Set JSON response header
        header('Content-Type: application/json');
        return true;
    }
}