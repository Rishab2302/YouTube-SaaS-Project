<?php

function getAppConfig(): array
{
    return [
        'app' => [
            'name' => $_ENV['APP_NAME'] ?? 'TaskFlow',
            'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
            'env' => $_ENV['APP_ENV'] ?? 'development',
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
        ],
        'session' => [
            'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120), // minutes
            'remember_me_lifetime' => (int)($_ENV['REMEMBER_ME_LIFETIME'] ?? 43200), // minutes (30 days)
            'cookie_secure' => $_ENV['APP_ENV'] === 'production',
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ],
        'mail' => [
            'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
            'host' => $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io',
            'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@taskflow.app',
            'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'TaskFlow',
        ],
        'security' => [
            'csrf_token_name' => '_token',
            'password_reset_token_lifetime' => 60, // minutes
            'email_verification_token_lifetime' => 1440, // minutes (24 hours)
            'max_login_attempts' => 5,
            'login_lockout_minutes' => 15,
            'password_reset_rate_limit' => 3, // per hour per email
        ]
    ];
}

function isProduction(): bool
{
    return $_ENV['APP_ENV'] === 'production';
}

function isDevelopment(): bool
{
    return $_ENV['APP_ENV'] === 'development';
}

function isDebugMode(): bool
{
    return filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
}