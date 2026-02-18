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
if (!isset($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}

// Simple router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove leading slash
$uri = ltrim($uri, '/');

// Basic routing logic
try {
    switch ($uri) {
        case '':
        case 'dashboard':
            handleDashboard();
            break;
        case 'login':
            handleLogin($method);
            break;
        case 'register':
            handleRegister($method);
            break;
        case 'logout':
            handleLogout();
            break;
        default:
            http_response_code(404);
            include __DIR__ . '/../src/views/errors/404.php';
            break;
    }
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());

    http_response_code(500);
    if (isDevelopment()) {
        echo "<h1>Application Error</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        include __DIR__ . '/../src/views/errors/500.php';
    }
}

// Route handlers (placeholder implementations)
function handleDashboard()
{
    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }

    // Placeholder dashboard content
    echo "<!DOCTYPE html>";
    echo "<html lang='en'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Dashboard - TaskFlow</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'>";
    echo "</head>";
    echo "<body>";
    echo "<div class='container mt-5'>";
    echo "<h1><i class='bi bi-speedometer2 me-2'></i>TaskFlow Dashboard</h1>";
    echo "<div class='alert alert-success'>";
    echo "<i class='bi bi-check-circle me-2'></i>Project scaffolding complete! Authentication and full functionality coming soon.";
    echo "</div>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'><i class='bi bi-list-check me-2'></i>Quick Stats</h5>";
    echo "<p class='card-text'>Total Tasks: 0</p>";
    echo "<p class='card-text'>Completed: 0</p>";
    echo "<p class='card-text'>In Progress: 0</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'><i class='bi bi-gear me-2'></i>System Status</h5>";
    echo "<p class='card-text'><i class='bi bi-database me-1'></i>Database: " . (Database::testConnection() ? "<span class='text-success'>Connected</span>" : "<span class='text-danger'>Disconnected</span>") . "</p>";
    echo "<p class='card-text'><i class='bi bi-server me-1'></i>Environment: " . $_ENV['APP_ENV'] . "</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "<div class='mt-4'>";
    echo "<a href='/logout' class='btn btn-outline-secondary'><i class='bi bi-box-arrow-right me-1'></i>Sign Out</a>";
    echo "</div>";
    echo "</div>";
    echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'></script>";
    echo "</body>";
    echo "</html>";
}

function handleLogin($method)
{
    if ($method === 'GET') {
        // Display login form
        echo "<!DOCTYPE html>";
        echo "<html lang='en'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>Login - TaskFlow</title>";
        echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
        echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'>";
        echo "</head>";
        echo "<body class='bg-body-tertiary'>";
        echo "<div class='container'>";
        echo "<div class='row justify-content-center'>";
        echo "<div class='col-md-6 col-lg-4'>";
        echo "<div class='card mt-5 shadow'>";
        echo "<div class='card-body'>";
        echo "<h2 class='card-title text-center mb-4'><i class='bi bi-person-circle me-2'></i>TaskFlow Login</h2>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='_token' value='" . $_SESSION['_token'] . "'>";
        echo "<div class='mb-3'>";
        echo "<label for='email' class='form-label'>Email</label>";
        echo "<input type='email' class='form-control' id='email' name='email' required>";
        echo "</div>";
        echo "<div class='mb-3'>";
        echo "<label for='password' class='form-label'>Password</label>";
        echo "<input type='password' class='form-control' id='password' name='password' required>";
        echo "</div>";
        echo "<div class='mb-3 form-check'>";
        echo "<input type='checkbox' class='form-check-input' id='remember' name='remember'>";
        echo "<label class='form-check-label' for='remember'>Remember me</label>";
        echo "</div>";
        echo "<button type='submit' class='btn btn-primary w-100'>Sign In</button>";
        echo "</form>";
        echo "<div class='text-center mt-3'>";
        echo "<p><a href='#' class='text-decoration-none'>Forgot your password?</a></p>";
        echo "<p>Don't have an account? <a href='/register' class='text-decoration-none'>Sign up</a></p>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</body>";
        echo "</html>";
    } else {
        // Placeholder login logic - for now just redirect to dashboard
        // TODO: Implement actual authentication
        $_SESSION['user_id'] = 1; // Temporary
        header('Location: /dashboard');
        exit;
    }
}

function handleRegister($method)
{
    if ($method === 'GET') {
        echo "<h1>Registration Page</h1>";
        echo "<p>Registration functionality coming soon...</p>";
        echo "<a href='/login'>Back to Login</a>";
    } else {
        echo "<p>Registration processing...</p>";
    }
}

function handleLogout()
{
    // Destroy session
    session_destroy();

    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    header('Location: /login');
    exit;
}