<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Category;
use Database;
use PDO;
use Exception;
use Email;
use Session;

class AuthController extends BaseController
{
    private User $userModel;
    private Category $categoryModel;
    private Email $email;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->categoryModel = new Category();
        $this->email = new Email();
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        $this->renderAuth('auth/login', [
            'title' => 'Login to TaskFlow',
            'flashes' => Session::getAllFlashes()
        ]);
    }

    /**
     * Handle login attempt
     * REQ-AUTH-010, REQ-AUTH-011, REQ-AUTH-014, REQ-AUTH-015, REQ-AUTH-016
     */
    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember_me']);

        // Server-side validation
        $errors = $this->validateLoginForm($email, $password);
        if (!empty($errors)) {
            Session::flashErrors($errors);
            Session::flashInput(['email' => $email]);
            $this->redirect('/login');
            return;
        }

        // Rate limiting check - REQ-AUTH-014
        if ($this->isRateLimited($email)) {
            Session::flash('error', 'Too many login attempts. Please try again in 15 minutes.');
            $this->redirect('/login');
            return;
        }

        try {
            $user = $this->userModel->findByEmail($email);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $this->recordLoginAttempt($email, false);
                Session::flash('error', 'Invalid email or password.');
                Session::flashInput(['email' => $email]);
                $this->redirect('/login');
                return;
            }

            // Check if account is verified - REQ-AUTH-006
            if (!$user['email_verified_at']) {
                Session::flash('error', 'Please verify your email address before logging in. <a href="/resend-verification" class="alert-link">Resend verification email</a>.');
                Session::flashInput(['email' => $email]);
                $this->redirect('/login');
                return;
            }

            // Successful login
            $this->recordLoginAttempt($email, true);
            Session::setUserId($user['id']);

            // Handle remember me - REQ-AUTH-013
            if ($remember) {
                $this->createRememberToken($user['id']);
            }

            // Redirect to intended URL or dashboard - REQ-AUTH-016
            $intendedUrl = Session::getIntendedUrl();
            $this->redirect($intendedUrl ?? '/dashboard');

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            Session::flash('error', 'An error occurred during login. Please try again.');
            Session::flashInput(['email' => $email]);
            $this->redirect('/login');
        }
    }

    /**
     * Show registration form
     * REQ-AUTH-001, REQ-AUTH-007
     */
    public function showRegister(): void
    {
        $this->renderAuth('auth/register', [
            'title' => 'Register for TaskFlow',
            'flashes' => Session::getAllFlashes(),
            'errors' => Session::getErrors(),
            'oldInput' => $_SESSION['old_input'] ?? []
        ]);
    }

    /**
     * Handle user registration
     * REQ-AUTH-001 through REQ-AUTH-008
     */
    public function register(): void
    {
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirmation'] ?? ''
        ];

        // Server-side validation - REQ-AUTH-008
        $errors = $this->validateRegistrationForm($data);
        if (!empty($errors)) {
            Session::flashErrors($errors);
            Session::flashInput([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email']
                // Don't flash passwords
            ]);
            $this->redirect('/register');
            return;
        }

        try {
            // Create user - REQ-AUTH-004
            $userId = $this->userModel->create($data);
            if (!$userId) {
                Session::flash('error', 'Failed to create account. Please try again.');
                Session::flashInput([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email']
                ]);
                $this->redirect('/register');
                return;
            }

            // Generate verification token - REQ-AUTH-005, REQ-SEC-008
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $this->userModel->updateVerificationToken($userId, $tokenHash, $expiresAt);

            // Send verification email - REQ-AUTH-005
            $user = $this->userModel->findById($userId);
            $this->email->sendVerificationEmail($user, $token);

            // Create default categories - REQ-CAT-008
            $this->categoryModel->createDefaultCategories($userId);

            Session::flash('success', 'Registration successful! Please check your email to verify your account.');
            $this->redirect('/login');

        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            Session::flash('error', 'An error occurred during registration. Please try again.');
            Session::flashInput([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email']
            ]);
            $this->redirect('/register');
        }
    }

    /**
     * Verify email address
     * REQ-AUTH-005, REQ-AUTH-006
     */
    public function verifyEmail(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            Session::flash('error', 'Invalid verification token.');
            $this->redirect('/login');
            return;
        }

        try {
            $tokenHash = hash('sha256', $token);
            $user = $this->userModel->findByVerificationToken($tokenHash);

            if (!$user) {
                $this->renderAuth('auth/verify-error', [
                    'title' => 'Verification Failed',
                    'message' => 'Invalid or expired verification token.',
                    'showResendOption' => true
                ]);
                return;
            }

            // Verify the email
            if ($this->userModel->verifyEmail($user['id'])) {
                Session::flash('success', 'Email verified successfully! You can now log in.');
                $this->redirect('/login');
            } else {
                Session::flash('error', 'Failed to verify email. Please try again.');
                $this->redirect('/login');
            }

        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            Session::flash('error', 'An error occurred during verification.');
            $this->redirect('/login');
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerification(): void
    {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Please enter a valid email address.');
            $this->redirect('/login');
            return;
        }

        try {
            $user = $this->userModel->findByEmail($email);
            if ($user && !$user['email_verified_at']) {
                // Generate new token
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $this->userModel->updateVerificationToken($user['id'], $tokenHash, $expiresAt);
                $this->email->sendVerificationEmail($user, $token);
            }

            // Always show success message for security
            Session::flash('success', 'If an unverified account with that email exists, we\'ve sent a new verification link.');

        } catch (Exception $e) {
            error_log("Resend verification error: " . $e->getMessage());
            Session::flash('error', 'An error occurred. Please try again.');
        }

        $this->redirect('/login');
    }

    /**
     * Handle logout
     * REQ-AUTH-020
     */
    public function logout(): void
    {
        // Clear remember token if exists
        if (Session::isAuthenticated()) {
            $stmt = $this->db->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
            $stmt->execute([Session::getUserId()]);
        }

        // Clear session
        Session::clearAuth();
        Session::destroy();

        Session::flash('success', 'You have been logged out successfully.');
        $this->redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword(): void
    {
        $this->renderAuth('auth/forgot-password', [
            'title' => 'Reset Password',
            'flashes' => Session::getAllFlashes()
        ]);
    }

    /**
     * Handle forgot password request
     * REQ-SEC-006: Rate limit password reset requests
     */
    public function forgotPassword(): void
    {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Please enter a valid email address.');
            $this->redirect('/forgot-password');
            return;
        }

        try {
            $user = $this->userModel->findByEmail($email);
            Session::flash('success', 'If an account with that email exists, we\'ve sent a password reset link.');

            if ($user) {
                // Create reset token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt = $this->db->prepare('
                    INSERT INTO password_resets (email, token, expires_at, created_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = VALUES(created_at)
                ');
                $stmt->execute([$email, hash('sha256', $token), $expiresAt]);

                $this->email->sendPasswordResetEmail($email, $token);
            }

            $this->redirect('/login');

        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            Session::flash('error', 'An error occurred. Please try again.');
            $this->redirect('/forgot-password');
        }
    }

    /**
     * Show reset password form
     */
    public function showResetPassword(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            Session::flash('error', 'Invalid reset token.');
            $this->redirect('/login');
            return;
        }

        // Verify token is valid and not expired
        $stmt = $this->db->prepare('
            SELECT email FROM password_resets
            WHERE token = ? AND expires_at > NOW()
        ');
        $stmt->execute([hash('sha256', $token)]);
        $reset = $stmt->fetch();

        if (!$reset) {
            Session::flash('error', 'Invalid or expired reset token.');
            $this->redirect('/login');
            return;
        }

        $this->renderAuth('auth/reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
            'email' => $reset['email'],
            'flashes' => Session::getAllFlashes(),
            'errors' => Session::getErrors()
        ]);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirmation'] ?? '';

        $errors = [];

        if (empty($password)) {
            $errors['password'][] = 'Password is required';
        } elseif (!$this->isValidPassword($password)) {
            $errors['password'][] = 'Password must be at least 8 characters and contain uppercase, lowercase, and number';
        }

        if ($password !== $confirmPassword) {
            $errors['password_confirmation'][] = 'Password confirmation does not match';
        }

        if (!empty($errors)) {
            Session::flashErrors($errors);
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        try {
            // Verify token
            $stmt = $this->db->prepare('
                SELECT email FROM password_resets
                WHERE token = ? AND expires_at > NOW()
            ');
            $stmt->execute([hash('sha256', $token)]);
            $reset = $stmt->fetch();

            if (!$reset) {
                Session::flash('error', 'Invalid or expired reset token.');
                $this->redirect('/login');
                return;
            }

            // Update password
            $user = $this->userModel->findByEmail($reset['email']);
            if ($user && $this->userModel->updatePassword($user['id'], $password)) {
                // Delete reset token
                $stmt = $this->db->prepare('DELETE FROM password_resets WHERE token = ?');
                $stmt->execute([hash('sha256', $token)]);

                Session::flash('success', 'Password reset successful! You can now log in.');
                $this->redirect('/login');
            } else {
                Session::flash('error', 'Failed to reset password. Please try again.');
                $this->redirect('/reset-password?token=' . urlencode($token));
            }

        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            Session::flash('error', 'An error occurred. Please try again.');
            $this->redirect('/reset-password?token=' . urlencode($token));
        }
    }

    /**
     * Validate login form
     */
    private function validateLoginForm(string $email, string $password): array
    {
        $errors = [];

        if (empty($email)) {
            $errors['email'][] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Please enter a valid email address';
        }

        if (empty($password)) {
            $errors['password'][] = 'Password is required';
        }

        return $errors;
    }

    /**
     * Validate registration form
     * REQ-AUTH-002, REQ-AUTH-003, REQ-AUTH-007, REQ-AUTH-008
     */
    private function validateRegistrationForm(array $data): array
    {
        $errors = [];

        // First name validation - REQ-AUTH-007
        if (empty($data['first_name'])) {
            $errors['first_name'][] = 'First name is required';
        } elseif (strlen($data['first_name']) > 100) {
            $errors['first_name'][] = 'First name must not exceed 100 characters';
        }

        // Last name validation - REQ-AUTH-007
        if (empty($data['last_name'])) {
            $errors['last_name'][] = 'Last name is required';
        } elseif (strlen($data['last_name']) > 100) {
            $errors['last_name'][] = 'Last name must not exceed 100 characters';
        }

        // Email validation - REQ-AUTH-001, REQ-AUTH-002
        if (empty($data['email'])) {
            $errors['email'][] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Please enter a valid email address';
        } elseif ($this->userModel->emailExists($data['email'])) {
            $errors['email'][] = 'An account with this email address already exists';
        }

        // Password validation - REQ-AUTH-003
        if (empty($data['password'])) {
            $errors['password'][] = 'Password is required';
        } elseif (!$this->isValidPassword($data['password'])) {
            $errors['password'][] = 'Password must be at least 8 characters and contain at least one uppercase letter, one lowercase letter, and one number';
        }

        // Password confirmation
        if ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'][] = 'Password confirmation does not match';
        }

        // Terms agreement validation
        if (empty($_POST['agree_terms'])) {
            $errors['agree_terms'][] = 'You must agree to the Terms of Service and Privacy Policy';
        }

        return $errors;
    }

    /**
     * Check if password meets requirements
     * REQ-AUTH-003: At least 8 chars, uppercase, lowercase, number
     */
    private function isValidPassword(string $password): bool
    {
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }

    /**
     * Check if IP/email is rate limited
     * REQ-AUTH-014: 5 failed attempts in 15 minutes
     */
    private function isRateLimited(string $email): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $timeWindow = date('Y-m-d H:i:s', strtotime('-15 minutes'));

        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM login_attempts
            WHERE (email = ? OR ip_address = ?)
            AND success = 0
            AND attempted_at > ?
        ');
        $stmt->execute([$email, $ip, $timeWindow]);

        return $stmt->fetchColumn() >= 5;
    }

    /**
     * Record login attempt
     * REQ-AUTH-015: Log all login attempts
     */
    private function recordLoginAttempt(string $email, bool $success): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $this->db->prepare('
            INSERT INTO login_attempts (email, ip_address, user_agent, success, attempted_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$email, $ip, $userAgent, $success ? 1 : 0]);
    }

    /**
     * Create remember me token
     * REQ-AUTH-013: Remember me functionality
     */
    private function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

        // Clear existing tokens for this user
        $stmt = $this->db->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        $stmt->execute([$userId]);

        // Create new token
        $stmt = $this->db->prepare('
            INSERT INTO remember_tokens (user_id, token, expires_at, created_at)
            VALUES (?, ?, ?, NOW())
        ');
        $stmt->execute([$userId, hash('sha256', $token), $expiresAt]);

        // Set cookie - REQ-AUTH-012
        setcookie('remember_token', $token, [
            'expires' => strtotime('+30 days'),
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}