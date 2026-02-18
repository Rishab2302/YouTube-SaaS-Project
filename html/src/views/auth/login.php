<div id="auth-container" class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Welcome Back
            </h1>
            <p class="auth-subtitle text-muted">Sign in to your TaskFlow account</p>
        </div>

        <?php if (!empty($flashes)): ?>
            <?php foreach ($flashes as $type => $message): ?>
                <div id="flash-message" class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $type === 'error' ? 'exclamation-circle' : ($type === 'success' ? 'check-circle' : 'info-circle') ?> me-2"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form id="login-form" method="POST" action="/login" class="auth-form">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>" />

            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope me-1"></i>
                    Email Address
                </label>
                <input
                    type="email"
                    class="form-control form-control-lg"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-1"></i>
                    Password
                </label>
                <div class="position-relative">
                    <input
                        type="password"
                        class="form-control form-control-lg"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <button
                        type="button"
                        class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 p-0"
                        id="toggle-password"
                        tabindex="-1"
                    >
                        <i class="bi bi-eye" id="password-icon"></i>
                    </button>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="remember_me"
                            name="remember_me"
                        >
                        <label class="form-check-label" for="remember_me">
                            Remember me
                        </label>
                    </div>
                </div>
                <div class="col text-end">
                    <a href="/forgot-password" class="text-decoration-none">
                        Forgot password?
                    </a>
                </div>
            </div>

            <button
                type="submit"
                class="btn btn-primary btn-lg w-100 mb-3"
                id="login-button"
            >
                <span class="button-text">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Sign In
                </span>
                <div class="spinner-border spinner-border-sm d-none" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </button>

            <div class="text-center">
                <p class="text-muted mb-0">
                    Don't have an account?
                    <a href="/register" class="text-decoration-none fw-medium">
                        Create one here
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('toggle-password').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('password-icon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        passwordIcon.className = 'bi bi-eye';
    }
});

// Form loading state
document.getElementById('login-form').addEventListener('submit', function() {
    const button = document.getElementById('login-button');
    const buttonText = button.querySelector('.button-text');
    const spinner = button.querySelector('.spinner-border');

    buttonText.classList.add('d-none');
    spinner.classList.remove('d-none');
    button.disabled = true;
});
</script>