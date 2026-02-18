<?php

namespace App\Controllers;

use PDO;
use Database;
use CsrfToken;

abstract class BaseController
{
    protected PDO $db;
    protected array $currentUser = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadCurrentUser();
    }

    /**
     * Render a view with the master layout
     */
    protected function render(string $view, array $data = []): void
    {
        // Check if this is an HTMX request
        if ($this->isHtmxRequest()) {
            $this->renderFragment($view, $data);
            return;
        }

        // Add common data available to all views
        $data = array_merge([
            'currentUser' => $this->currentUser,
            'csrfToken' => CsrfToken::get(),
            'appConfig' => getAppConfig(),
            'isAuthenticated' => $this->isAuthenticated(),
            'currentRoute' => $this->getCurrentRoute(),
        ], $data);

        // Extract data to variables
        extract($data);

        // Start output buffering for the view content
        ob_start();
        $viewFile = __DIR__ . "/../views/{$view}.php";

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }

        include $viewFile;
        $content = ob_get_clean();

        // Render with layout
        include __DIR__ . '/../views/layouts/app.php';
    }

    /**
     * Render a view fragment without layout (for HTMX responses)
     */
    protected function renderFragment(string $view, array $data = []): void
    {
        // Add common data
        $data = array_merge([
            'currentUser' => $this->currentUser,
            'csrfToken' => CsrfToken::get(),
            'appConfig' => getAppConfig(),
            'isAuthenticated' => $this->isAuthenticated(),
        ], $data);

        // Extract data to variables
        extract($data);

        $viewFile = __DIR__ . "/../views/{$view}.php";

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }

        include $viewFile;
    }

    /**
     * Render authentication layout (for login/register pages)
     */
    protected function renderAuth(string $view, array $data = []): void
    {
        // Add common data
        $data = array_merge([
            'csrfToken' => CsrfToken::get(),
            'appConfig' => getAppConfig(),
        ], $data);

        // Extract data to variables
        extract($data);

        // Start output buffering for the view content
        ob_start();
        $viewFile = __DIR__ . "/../views/{$view}.php";

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }

        include $viewFile;
        $content = ob_get_clean();

        // Render with auth layout
        include __DIR__ . '/../views/layouts/auth.php';
    }

    /**
     * Redirect to a specific path
     */
    protected function redirect(string $path, int $status = 302): void
    {
        // Handle HTMX redirects
        if ($this->isHtmxRequest()) {
            header("HX-Redirect: {$path}");
            exit;
        }

        // Regular redirect
        header("Location: {$path}", true, $status);
        exit;
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Return success response with optional message
     */
    protected function success(string $message = 'Operation completed successfully', array $data = []): void
    {
        if ($this->isHtmxRequest()) {
            // For HTMX, trigger a toast notification
            header('HX-Trigger: showToast');
            header('HX-Trigger-After-Settle: ' . json_encode([
                'showToast' => [
                    'type' => 'success',
                    'message' => $message
                ]
            ]));
        } else {
            // For regular requests, redirect with flash message
            $_SESSION['flash_success'] = $message;
        }

        $this->json(array_merge(['success' => true, 'message' => $message], $data));
    }

    /**
     * Return error response with message
     */
    protected function error(string $message = 'An error occurred', array $data = [], int $status = 400): void
    {
        if ($this->isHtmxRequest()) {
            // For HTMX, trigger an error toast
            header('HX-Trigger: showToast');
            header('HX-Trigger-After-Settle: ' . json_encode([
                'showToast' => [
                    'type' => 'error',
                    'message' => $message
                ]
            ]));
        }

        $this->json(array_merge(['success' => false, 'message' => $message], $data), $status);
    }

    /**
     * Get the current authenticated user
     */
    protected function currentUser(): array
    {
        return $this->currentUser;
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return !empty($this->currentUser);
    }

    /**
     * Check if the request is from HTMX
     */
    protected function isHtmxRequest(): bool
    {
        return isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
    }

    /**
     * Load current user data from session
     */
    private function loadCurrentUser(): void
    {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            try {
                $stmt = $this->db->prepare('
                    SELECT id, first_name, last_name, email, theme_preference, timezone, created_at
                    FROM users
                    WHERE id = ? AND deleted_at IS NULL
                ');
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();

                if ($user) {
                    $this->currentUser = $user;
                } else {
                    // User not found or deleted, clear session
                    $this->clearUserSession();
                }
            } catch (\Exception $e) {
                error_log("Error loading current user: " . $e->getMessage());
                $this->clearUserSession();
            }
        }
    }

    /**
     * Clear user session data
     */
    protected function clearUserSession(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
        $this->currentUser = [];
    }

    /**
     * Get the current route for navigation highlighting
     */
    private function getCurrentRoute(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return ltrim($uri, '/') ?: 'dashboard';
    }

    /**
     * Set flash message for next request
     */
    protected function setFlash(string $type, string $message): void
    {
        $_SESSION["flash_{$type}"] = $message;
    }

    /**
     * Get and clear flash message
     */
    protected function getFlash(string $type): ?string
    {
        $message = $_SESSION["flash_{$type}"] ?? null;
        if ($message) {
            unset($_SESSION["flash_{$type}"]);
        }
        return $message;
    }

    /**
     * Get all flash messages and clear them
     */
    protected function getAllFlashes(): array
    {
        $flashes = [];
        $types = ['success', 'error', 'warning', 'info'];

        foreach ($types as $type) {
            $message = $this->getFlash($type);
            if ($message) {
                $flashes[$type] = $message;
            }
        }

        return $flashes;
    }

    /**
     * Validate request data against rules
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $fieldRules = explode('|', $fieldRules);

            foreach ($fieldRules as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;

                switch ($ruleName) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = ucfirst($field) . ' is required';
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = ucfirst($field) . ' must be a valid email address';
                        }
                        break;

                    case 'min':
                        if (!empty($value) && strlen($value) < (int)$ruleValue) {
                            $errors[$field][] = ucfirst($field) . " must be at least {$ruleValue} characters";
                        }
                        break;

                    case 'max':
                        if (!empty($value) && strlen($value) > (int)$ruleValue) {
                            $errors[$field][] = ucfirst($field) . " must not exceed {$ruleValue} characters";
                        }
                        break;

                    case 'unique':
                        if (!empty($value)) {
                            [$table, $column] = explode(',', $ruleValue);
                            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
                            $stmt->execute([$value]);
                            if ($stmt->fetchColumn() > 0) {
                                $errors[$field][] = ucfirst($field) . ' is already taken';
                            }
                        }
                        break;
                }
            }
        }

        return $errors;
    }
}