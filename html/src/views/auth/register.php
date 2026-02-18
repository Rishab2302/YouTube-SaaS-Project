<div id="auth-container" class="auth-container" x-data="registrationForm()">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">
                <i class="bi bi-person-plus me-2"></i>
                Create Account
            </h1>
            <p class="auth-subtitle text-muted">Join TaskFlow and start organizing your tasks</p>
        </div>

        <!-- Flash Messages -->
        <?php if (!empty($flashes)): ?>
            <?php foreach ($flashes as $type => $message): ?>
                <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $type === 'error' ? 'exclamation-circle' : ($type === 'success' ? 'check-circle' : 'info-circle') ?> me-2"></i>
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form id="register-form" method="POST" action="/register" class="auth-form" novalidate @submit="handleSubmit">
            <?= CsrfToken::field() ?>

            <!-- First Name -->
            <div class="mb-3">
                <label for="first_name" class="form-label">
                    <i class="bi bi-person me-1"></i>
                    First Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    class="form-control form-control-lg <?= !empty($errors['first_name']) ? 'is-invalid' : '' ?>"
                    id="first_name"
                    name="first_name"
                    placeholder="Enter your first name"
                    value="<?= Session::getOldInput('first_name') ?>"
                    required
                    maxlength="100"
                    autocomplete="given-name"
                    x-model="form.first_name"
                >
                <?php if (!empty($errors['first_name'])): ?>
                    <div class="invalid-feedback">
                        <?= implode('<br>', $errors['first_name']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Last Name -->
            <div class="mb-3">
                <label for="last_name" class="form-label">
                    <i class="bi bi-person me-1"></i>
                    Last Name <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    class="form-control form-control-lg <?= !empty($errors['last_name']) ? 'is-invalid' : '' ?>"
                    id="last_name"
                    name="last_name"
                    placeholder="Enter your last name"
                    value="<?= Session::getOldInput('last_name') ?>"
                    required
                    maxlength="100"
                    autocomplete="family-name"
                    x-model="form.last_name"
                >
                <?php if (!empty($errors['last_name'])): ?>
                    <div class="invalid-feedback">
                        <?= implode('<br>', $errors['last_name']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope me-1"></i>
                    Email Address <span class="text-danger">*</span>
                </label>
                <input
                    type="email"
                    class="form-control form-control-lg <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                    value="<?= Session::getOldInput('email') ?>"
                    required
                    autocomplete="email"
                    x-model="form.email"
                >
                <?php if (!empty($errors['email'])): ?>
                    <div class="invalid-feedback">
                        <?= implode('<br>', $errors['email']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-1"></i>
                    Password <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                    <input
                        type="password"
                        class="form-control form-control-lg <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                        id="password"
                        name="password"
                        placeholder="Create a strong password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        x-model="form.password"
                        @input="validatePassword()"
                    >
                    <button
                        type="button"
                        class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 p-0"
                        @click="togglePasswordVisibility('password')"
                        tabindex="-1"
                    >
                        <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>

                <!-- Password Strength Indicator -->
                <div class="password-strength mt-2" x-show="form.password.length > 0">
                    <div class="small text-muted mb-1">Password Requirements:</div>
                    <ul class="list-unstyled small">
                        <li :class="passwordChecks.length ? 'text-success' : 'text-muted'">
                            <i class="bi" :class="passwordChecks.length ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                            At least 8 characters
                        </li>
                        <li :class="passwordChecks.uppercase ? 'text-success' : 'text-muted'">
                            <i class="bi" :class="passwordChecks.uppercase ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                            One uppercase letter
                        </li>
                        <li :class="passwordChecks.lowercase ? 'text-success' : 'text-muted'">
                            <i class="bi" :class="passwordChecks.lowercase ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                            One lowercase letter
                        </li>
                        <li :class="passwordChecks.number ? 'text-success' : 'text-muted'">
                            <i class="bi" :class="passwordChecks.number ? 'bi-check-circle-fill' : 'bi-circle'"></i>
                            One number
                        </li>
                    </ul>
                </div>

                <?php if (!empty($errors['password'])): ?>
                    <div class="invalid-feedback d-block">
                        <?= implode('<br>', $errors['password']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Password Confirmation -->
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">
                    <i class="bi bi-lock-fill me-1"></i>
                    Confirm Password <span class="text-danger">*</span>
                </label>
                <div class="position-relative">
                    <input
                        type="password"
                        class="form-control form-control-lg <?= !empty($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Confirm your password"
                        required
                        autocomplete="new-password"
                        x-model="form.password_confirmation"
                        @input="validatePasswordMatch()"
                    >
                    <button
                        type="button"
                        class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 p-0"
                        @click="togglePasswordVisibility('password_confirmation')"
                        tabindex="-1"
                    >
                        <i class="bi" :class="showPasswordConfirmation ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>

                <!-- Password Match Indicator -->
                <div class="mt-1" x-show="form.password_confirmation.length > 0">
                    <small :class="passwordsMatch ? 'text-success' : 'text-danger'">
                        <i class="bi" :class="passwordsMatch ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                        <span x-text="passwordsMatch ? 'Passwords match' : 'Passwords do not match'"></span>
                    </small>
                </div>

                <?php if (!empty($errors['password_confirmation'])): ?>
                    <div class="invalid-feedback d-block">
                        <?= implode('<br>', $errors['password_confirmation']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Terms Agreement -->
            <div class="mb-4">
                <div class="form-check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="agree_terms"
                        required
                        x-model="form.agree_terms"
                    >
                    <label class="form-check-label small" for="agree_terms">
                        I agree to the <a href="#" class="text-decoration-none">Terms of Service</a>
                        and <a href="#" class="text-decoration-none">Privacy Policy</a> <span class="text-danger">*</span>
                    </label>
                </div>
            </div>

            <!-- Debug Info (temporary) -->
            <div class="small text-muted mb-2" x-show="true">
                <div>First Name: <span x-text="form.first_name.trim().length > 0 ? '✓' : '✗'"></span></div>
                <div>Last Name: <span x-text="form.last_name.trim().length > 0 ? '✓' : '✗'"></span></div>
                <div>Email: <span x-text="form.email.trim().length > 0 ? '✓' : '✗'"></span></div>
                <div>Password Valid: <span x-text="isPasswordValid ? '✓' : '✗'"></span></div>
                <div>Passwords Match: <span x-text="passwordsMatch ? '✓' : '✗'"></span></div>
                <div>Terms Agreed: <span x-text="form.agree_terms ? '✓' : '✗'"></span></div>
                <div>Form Valid: <span x-text="isFormValid ? '✓' : '✗'"></span></div>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="btn btn-primary btn-lg w-100 mb-3"
                id="submit-button"
            >
                <span x-text="isSubmitting ? 'Creating Account...' : 'Create Account'">Create Account</span>
            </button>

            <!-- Login Link -->
            <div class="text-center">
                <p class="text-muted mb-0">
                    Already have an account?
                    <a href="/login" class="text-decoration-none fw-medium">
                        Sign in here
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
function registrationForm() {
    return {
        form: {
            first_name: '<?= Session::getOldInput('first_name') ?>',
            last_name: '<?= Session::getOldInput('last_name') ?>',
            email: '<?= Session::getOldInput('email') ?>',
            password: '',
            password_confirmation: '',
            agree_terms: false
        },

        showPassword: false,
        showPasswordConfirmation: false,
        isSubmitting: false,

        passwordChecks: {
            length: false,
            uppercase: false,
            lowercase: false,
            number: false
        },

        passwordsMatch: false,

        get isFormValid() {
            return this.form.first_name.trim().length > 0 &&
                   this.form.last_name.trim().length > 0 &&
                   this.form.email.trim().length > 0 &&
                   this.isPasswordValid &&
                   this.passwordsMatch &&
                   this.form.agree_terms;
        },

        get isPasswordValid() {
            return this.passwordChecks.length &&
                   this.passwordChecks.uppercase &&
                   this.passwordChecks.lowercase &&
                   this.passwordChecks.number;
        },

        validatePassword() {
            const password = this.form.password;
            this.passwordChecks = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password)
            };
            this.validatePasswordMatch();
        },

        validatePasswordMatch() {
            this.passwordsMatch = this.form.password === this.form.password_confirmation &&
                                this.form.password_confirmation.length > 0;
        },

        togglePasswordVisibility(field) {
            if (field === 'password') {
                this.showPassword = !this.showPassword;
                const input = document.getElementById('password');
                input.type = this.showPassword ? 'text' : 'password';
            } else if (field === 'password_confirmation') {
                this.showPasswordConfirmation = !this.showPasswordConfirmation;
                const input = document.getElementById('password_confirmation');
                input.type = this.showPasswordConfirmation ? 'text' : 'password';
            }
        },

        handleSubmit(event) {
            this.isSubmitting = true;
            // Let the form submit naturally
        }
    }
}
</script>