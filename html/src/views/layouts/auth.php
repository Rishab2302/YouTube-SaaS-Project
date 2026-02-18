<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TaskFlow - Sign in to your account">

    <?= CsrfToken::metaTag() ?>

    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - TaskFlow' : 'TaskFlow - Authentication' ?></title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link href="/css/app.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <style>
        body {
            background: linear-gradient(135deg, var(--bs-primary-bg-subtle) 0%, var(--bs-secondary-bg-subtle) 100%);
            min-height: 100vh;
        }

        .auth-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        [data-bs-theme="dark"] .auth-card {
            background: rgba(13, 13, 13, 0.95);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .auth-brand {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--bs-primary);
        }

        .btn-auth {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center" hx-headers='{"X-CSRF-Token": "<?= $csrfToken ?>"}' id="auth-body">
    <!-- Theme Toggle (top-right corner) -->
    <div class="position-absolute top-0 end-0 p-3" id="auth-theme-toggle">
        <div class="dropdown">
            <button class="btn btn-link p-2" data-bs-toggle="dropdown" aria-expanded="false" id="auth-theme-btn">
                <i class="bi bi-circle-half fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><button class="dropdown-item" data-bs-theme-value="light" id="auth-theme-light">
                    <i class="bi bi-sun-fill me-2"></i>Light
                </button></li>
                <li><button class="dropdown-item" data-bs-theme-value="dark" id="auth-theme-dark">
                    <i class="bi bi-moon-stars-fill me-2"></i>Dark
                </button></li>
                <li><button class="dropdown-item" data-bs-theme-value="auto" id="auth-theme-auto">
                    <i class="bi bi-circle-half me-2"></i>Auto
                </button></li>
            </ul>
        </div>
    </div>

    <!-- Main Auth Container -->
    <div class="container" id="auth-container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-8 col-md-6 col-lg-5 col-xl-4">
                <!-- Auth Card -->
                <div class="card auth-card shadow-lg border-0 rounded-4" id="auth-card">
                    <div class="card-body p-4 p-md-5">
                        <!-- Brand -->
                        <div class="text-center mb-4" id="auth-brand-container">
                            <div class="auth-brand text-primary" id="auth-brand">
                                <i class="bi bi-check2-square me-2"></i>TaskFlow
                            </div>
                            <?php if (isset($authSubtitle)): ?>
                                <p class="text-body-secondary mb-0" id="auth-subtitle"><?= htmlspecialchars($authSubtitle) ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Flash Messages -->
                        <?php
                        $flashes = [
                            'success' => $_SESSION['flash_success'] ?? null,
                            'error' => $_SESSION['flash_error'] ?? null,
                            'warning' => $_SESSION['flash_warning'] ?? null,
                            'info' => $_SESSION['flash_info'] ?? null
                        ];

                        foreach ($flashes as $type => $message) {
                            if ($message) {
                                unset($_SESSION["flash_{$type}"]);
                                $alertClass = $type === 'error' ? 'danger' : $type;
                                $icon = match($type) {
                                    'success' => 'check-circle',
                                    'error' => 'exclamation-triangle',
                                    'warning' => 'exclamation-triangle',
                                    'info' => 'info-circle',
                                    default => 'info-circle'
                                };
                                echo "<div class='alert alert-{$alertClass} alert-dismissible fade show mb-4' role='alert' id='auth-flash-{$type}'>
                                        <i class='bi bi-{$icon} me-2'></i>{$message}
                                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                                      </div>";
                            }
                        }
                        ?>

                        <!-- Auth Content -->
                        <div id="auth-content">
                            <?= $content ?>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer bg-transparent text-center border-0 pb-4" id="auth-footer">
                        <small class="text-body-secondary">
                            © <?= date('Y') ?> TaskFlow. Built with ❤️ for productivity.
                        </small>
                    </div>
                </div>

                <!-- Additional Links -->
                <?php if (isset($showBackToApp) && $showBackToApp): ?>
                <div class="text-center mt-3" id="back-to-app">
                    <a href="/dashboard" class="text-decoration-none small">
                        <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="auth-toast-container"></div>

    <!-- Loading Indicator -->
    <div id="loading-indicator" class="htmx-indicator position-fixed top-50 start-50 translate-middle">
        <div class="d-flex align-items-center bg-white rounded p-3 shadow">
            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            Loading...
        </div>
    </div>

    <!-- Scripts -->
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@2.0.2"></script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="/js/app.js"></script>

    <!-- Initialize tooltips -->
    <script>
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Set HTMX config
        htmx.config.defaultSwapStyle = 'innerHTML';
    </script>
</body>
</html>