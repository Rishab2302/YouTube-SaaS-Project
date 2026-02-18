<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TaskFlow - A powerful task management application">

    <?= CsrfToken::metaTag() ?>

    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - TaskFlow' : 'TaskFlow - Task Management' ?></title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link href="/css/app.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
</head>
<body hx-headers='{"X-CSRF-Token": "<?= $csrfToken ?>"}'>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary fixed-top shadow-sm" id="main-navbar">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand fw-bold text-primary" href="/dashboard" id="navbar-brand">
                <i class="bi bi-check2-square me-2"></i>TaskFlow
            </a>

            <!-- Mobile sidebar toggle -->
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" id="sidebar-toggle">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar items -->
            <div class="d-flex align-items-center gap-2">
                <!-- Quick Add Task Button -->
                <div class="dropdown" x-data="{ open: false }" id="quick-add-dropdown">
                    <button class="btn btn-primary btn-sm d-none d-md-flex align-items-center" @click="open = !open" id="quick-add-btn">
                        <i class="bi bi-plus-lg me-1"></i> New Task
                    </button>

                    <!-- Quick add form (will be implemented later) -->
                    <div x-show="open" x-transition @click.outside="open = false"
                         class="dropdown-menu dropdown-menu-end show position-absolute mt-2 p-3"
                         style="width: 320px; right: 0;" id="quick-add-form">
                        <form hx-post="/tasks" hx-target="#task-list" hx-swap="afterbegin" @htmx:after-request="open = false">
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" name="title" placeholder="Task title..." required>
                            </div>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                                <input type="date" class="form-control form-control-sm" name="due_date">
                                <button type="submit" class="btn btn-primary btn-sm">Add</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Notification Bell -->
                <button class="btn btn-link position-relative p-2" id="notifications-btn"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notification-badge">
                        <span class="visually-hidden">unread notifications</span>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" id="notifications-dropdown">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <li><span class="dropdown-item-text text-muted small">No new notifications</span></li>
                </ul>

                <!-- Theme Toggle -->
                <div class="dropdown" id="theme-dropdown">
                    <button class="btn btn-link p-2" data-bs-toggle="dropdown" aria-expanded="false" id="theme-toggle">
                        <i class="bi bi-circle-half fs-5"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" data-bs-theme-value="light" id="theme-light">
                            <i class="bi bi-sun-fill me-2"></i>Light
                        </button></li>
                        <li><button class="dropdown-item" data-bs-theme-value="dark" id="theme-dark">
                            <i class="bi bi-moon-stars-fill me-2"></i>Dark
                        </button></li>
                        <li><button class="dropdown-item" data-bs-theme-value="auto" id="theme-auto">
                            <i class="bi bi-circle-half me-2"></i>Auto
                        </button></li>
                    </ul>
                </div>

                <!-- User Menu -->
                <div class="dropdown" id="user-dropdown">
                    <button class="btn btn-link p-2 d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" id="user-menu">
                        <i class="bi bi-person-circle fs-5 me-1"></i>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($currentUser['first_name'] ?? 'User') ?></span>
                        <i class="bi bi-chevron-down ms-1 d-none d-md-inline"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">
                            <?= htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) ?>
                        </h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/profile" id="profile-link">
                            <i class="bi bi-gear me-2"></i>Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="/logout" class="m-0">
                                <?= CsrfToken::field() ?>
                                <button type="submit" class="dropdown-item text-danger" id="logout-btn">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Layout -->
    <div class="container-fluid" style="padding-top: 56px;" id="main-container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 d-none d-lg-block bg-body-tertiary border-end" id="sidebar-desktop">
                <div class="sticky-top" style="top: 56px; height: calc(100vh - 56px); overflow-y: auto;">
                    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
                </div>
            </div>

            <!-- Mobile Sidebar (Offcanvas) -->
            <div class="offcanvas offcanvas-start bg-body-tertiary" tabindex="-1" id="sidebarOffcanvas">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title text-primary fw-bold" id="sidebar-title">
                        <i class="bi bi-check2-square me-2"></i>TaskFlow
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" id="sidebar-close"></button>
                </div>
                <div class="offcanvas-body p-0">
                    <?php include __DIR__ . '/../partials/sidebar.php'; ?>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-lg-10 px-4 py-3" id="main-content">
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
                        echo "<div class='alert alert-{$alertClass} alert-dismissible fade show' role='alert' id='flash-{$type}'>
                                <i class='bi bi-info-circle me-2'></i>{$message}
                                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                              </div>";
                    }
                }
                ?>

                <!-- Page Content -->
                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"></div>

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
    <!-- jQuery (optional - only if needed) -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script> -->

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@2.0.2"></script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="/js/app.js"></script>

    <!-- Initialize tooltips and other Bootstrap components -->
    <script>
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Set global HTMX loading indicator
        htmx.config.defaultSwapStyle = 'innerHTML';
        htmx.config.defaultSwapDelay = 0;
        htmx.config.defaultSettleDelay = 0;
    </script>
</body>
</html>