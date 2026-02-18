<?php

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Load application configuration
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Load helper classes
require_once __DIR__ . '/../src/helpers/Router.php';
require_once __DIR__ . '/../src/helpers/Middleware.php';
require_once __DIR__ . '/../src/helpers/CsrfToken.php';

// Start session with secure configuration
$config = getAppConfig();

session_set_cookie_params([
    'lifetime' => $config['session']['lifetime'] * 60, // Convert minutes to seconds
    'path' => '/',
    'domain' => '',
    'secure' => $config['session']['cookie_secure'], // HTTPS only in production
    'httponly' => $config['session']['cookie_httponly'], // Prevent JavaScript access
    'samesite' => $config['session']['cookie_samesite'] // CSRF protection
]);

session_start();

// Error handling based on environment
if (isDevelopment()) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
}

// Initialize CSRF token
CsrfToken::get();

// Create router instance
$router = new Router();

// Register middleware
$router->registerMiddleware('auth', [Middleware::class, 'auth']);
$router->registerMiddleware('guest', [Middleware::class, 'guest']);
$router->registerMiddleware('csrf', [Middleware::class, 'csrf']);

// Public Routes (Unauthenticated)
$router->get('/login', 'AuthController@showLogin', ['guest']);
$router->post('/login', 'AuthController@login', ['guest', 'csrf']);
$router->get('/register', 'AuthController@showRegister', ['guest']);
$router->post('/register', 'AuthController@register', ['guest', 'csrf']);
$router->get('/verify-email', 'AuthController@verifyEmail', ['guest']);
$router->post('/resend-verification', 'AuthController@resendVerification', ['guest', 'csrf']);
$router->get('/forgot-password', 'AuthController@showForgotPassword', ['guest']);
$router->post('/forgot-password', 'AuthController@forgotPassword', ['guest', 'csrf']);
$router->get('/reset-password', 'AuthController@showResetPassword', ['guest']);
$router->post('/reset-password', 'AuthController@resetPassword', ['guest', 'csrf']);

// Protected Routes (Authenticated)
$router->get('/', 'DashboardController@index', ['auth']);
$router->get('/dashboard', 'DashboardController@index', ['auth']);

// Task Management Routes
$router->get('/tasks', 'TaskController@index', ['auth']);
$router->post('/tasks', 'TaskController@store', ['auth', 'csrf']);
$router->get('/tasks/{id}', 'TaskController@show', ['auth']);
$router->put('/tasks/{id}', 'TaskController@update', ['auth', 'csrf']);
$router->delete('/tasks/{id}', 'TaskController@destroy', ['auth', 'csrf']);
$router->patch('/tasks/{id}/status', 'TaskController@updateStatus', ['auth', 'csrf']);
$router->patch('/tasks/{id}/toggle', 'TaskController@toggle', ['auth', 'csrf']);

// Kanban Board
$router->get('/kanban', 'KanbanController@index', ['auth']);

// Calendar View
$router->get('/calendar', 'CalendarController@index', ['auth']);

// Trash Management
$router->get('/trash', 'TrashController@index', ['auth']);
$router->post('/trash/{id}/restore', 'TrashController@restore', ['auth', 'csrf']);
$router->delete('/trash/{id}', 'TrashController@permanentDelete', ['auth', 'csrf']);
$router->delete('/trash', 'TrashController@emptyTrash', ['auth', 'csrf']);

// Sub-tasks
$router->get('/tasks/{id}/subtasks', 'SubTaskController@index', ['auth']);
$router->post('/tasks/{id}/subtasks', 'SubTaskController@store', ['auth', 'csrf']);
$router->patch('/subtasks/{id}/toggle', 'SubTaskController@toggle', ['auth', 'csrf']);
$router->delete('/subtasks/{id}', 'SubTaskController@destroy', ['auth', 'csrf']);

// Categories Management
$router->get('/categories', 'CategoryController@index', ['auth']);
$router->post('/categories', 'CategoryController@store', ['auth', 'csrf']);
$router->put('/categories/{id}', 'CategoryController@update', ['auth', 'csrf']);
$router->delete('/categories/{id}', 'CategoryController@destroy', ['auth', 'csrf']);

// Profile/Settings
$router->get('/profile', 'ProfileController@show', ['auth']);
$router->put('/profile', 'ProfileController@update', ['auth', 'csrf']);
$router->put('/profile/password', 'ProfileController@updatePassword', ['auth', 'csrf']);
$router->delete('/profile', 'ProfileController@deleteAccount', ['auth', 'csrf']);

// Logout
$router->post('/logout', 'AuthController@logout', ['auth', 'csrf']);

// Handle the request
try {
    $router->resolve();
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());

    http_response_code(500);
    if (isDevelopment()) {
        echo "<h1>Application Error</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        // Load 500 error page
        if (file_exists(__DIR__ . '/../src/views/errors/500.php')) {
            include __DIR__ . '/../src/views/errors/500.php';
        } else {
            echo '<!DOCTYPE html>
            <html>
            <head><title>500 Internal Server Error</title></head>
            <body>
                <h1>500 - Internal Server Error</h1>
                <p>Something went wrong. Please try again later.</p>
                <a href="/">Go Home</a>
            </body>
            </html>';
        }
    }
}