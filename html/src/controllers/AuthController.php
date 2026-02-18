<?php

namespace App\Controllers;

use Database;
use PDO;
use Exception;

class AuthController extends BaseController
{
    /**
     * Show login form
     */
    public function showLogin(): void
    {
        $this->renderAuth('auth/login', [
            'title' => 'Login to TaskFlow',
            'flashes' => $this->getAllFlashes()
        ]);
    }

    /**
     * Handle login attempt
     */
    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember_me']);

        // Validate input
        $errors = $this->validate([
            'email' => $email,
            'password' => $password
        ], [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (!empty($errors)) {
            $this->setFlash('error', 'Please check your input and try again.');
            $this->redirect('/login');
            return;
        }

        // Rate limiting check
        if ($this->isRateLimited($email)) {
            $this->setFlash('error', 'Too many login attempts. Please try again later.');
            $this->redirect('/login');
            return;
        }

        try {
            // Find user
            $stmt = $this->db->prepare('
                SELECT id, first_name, last_name, email, password_hash, email_verified_at
                FROM users
                WHERE email = ? AND deleted_at IS NULL
            ');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $this->recordLoginAttempt($email, false);
                $this->setFlash('error', 'Invalid email or password.');
                $this->redirect('/login');
                return;
            }

            // Check if account is verified
            if (!$user['email_verified_at']) {
                $this->setFlash('error', 'Please verify your email address before logging in.');
                $this->redirect('/login');
                return;
            }

            // Successful login
            $this->recordLoginAttempt($email, true);

            // Create session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login_time'] = time();

            // Handle remember me
            if ($remember) {
                $this->createRememberToken($user['id']);
            }

            $this->redirect('/dashboard');

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $this->setFlash('error', 'An error occurred during login. Please try again.');
            $this->redirect('/login');
        }
    }

    /**
     * Show registration form
     */
    public function showRegister(): void
    {
        $this->renderAuth('auth/register', [
            'title' => 'Register for TaskFlow',
            'flashes' => $this->getAllFlashes()
        ]);
    }

    /**
     * Handle user registration
     */
    public function register(): void
    {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate input
        $errors = $this->validate([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password
        ], [
            'first_name' => 'required|min:2|max:50',
            'last_name' => 'required|min:2|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|max:255'
        ]);

        // Check password confirmation
        if ($password !== $confirmPassword) {
            $errors['confirm_password'][] = 'Password confirmation does not match';
        }

        if (!empty($errors)) {
            $this->setFlash('error', 'Please correct the errors below.');
            // In a real app, you'd pass the errors and old input back
            $this->redirect('/register');
            return;
        }

        try {
            // Create user
            $verificationToken = bin2hex(random_bytes(32));

            $stmt = $this->db->prepare('
                INSERT INTO users (first_name, last_name, email, password_hash, verification_token, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ');

            $stmt->execute([
                $firstName,
                $lastName,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $verificationToken
            ]);

            // In a real app, send verification email here
            // $this->sendVerificationEmail($email, $verificationToken);

            $this->setFlash('success', 'Registration successful! Please check your email to verify your account.');
            $this->redirect('/login');

        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $this->setFlash('error', 'An error occurred during registration. Please try again.');
            $this->redirect('/register');
        }
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        // Clear remember token if exists
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->db->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
            $stmt->execute([$_SESSION['user_id']]);
        }

        // Clear session
        $this->clearUserSession();
        session_destroy();

        $this->setFlash('success', 'You have been logged out successfully.');
        $this->redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword(): void
    {
        $this->renderAuth('auth/forgot-password', [
            'title' => 'Reset Password',
            'flashes' => $this->getAllFlashes()
        ]);
    }

    /**
     * Handle forgot password request
     */
    public function forgotPassword(): void
    {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Please enter a valid email address.');
            $this->redirect('/forgot-password');
            return;
        }

        try {
            // Check if user exists
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? AND deleted_at IS NULL');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Always show success message for security (don't reveal if email exists)
            $this->setFlash('success', 'If an account with that email exists, we\'ve sent a password reset link.');

            if ($user) {
                // Create reset token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt = $this->db->prepare('
                    INSERT INTO password_resets (email, token, expires_at, created_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = VALUES(created_at)
                ');
                $stmt->execute([$email, $token, $expiresAt]);

                // In a real app, send reset email here
                // $this->sendPasswordResetEmail($email, $token);
            }

            $this->redirect('/login');

        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $this->setFlash('error', 'An error occurred. Please try again.');
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
            $this->setFlash('error', 'Invalid reset token.');
            $this->redirect('/login');
            return;
        }

        // Verify token is valid and not expired
        $stmt = $this->db->prepare('
            SELECT email FROM password_resets
            WHERE token = ? AND expires_at > NOW()
        ');
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            $this->setFlash('error', 'Invalid or expired reset token.');
            $this->redirect('/login');
            return;
        }

        $this->renderAuth('auth/reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
            'email' => $reset['email'],
            'flashes' => $this->getAllFlashes()
        ]);
    }

    /**
     * Handle password reset
     */
    public function resetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($password) || $password !== $confirmPassword) {
            $this->setFlash('error', 'Please check your input and try again.');
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        if (strlen($password) < 8) {
            $this->setFlash('error', 'Password must be at least 8 characters.');
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        try {
            // Verify token
            $stmt = $this->db->prepare('
                SELECT email FROM password_resets
                WHERE token = ? AND expires_at > NOW()
            ');
            $stmt->execute([$token]);
            $reset = $stmt->fetch();

            if (!$reset) {
                $this->setFlash('error', 'Invalid or expired reset token.');
                $this->redirect('/login');
                return;
            }

            // Update password
            $stmt = $this->db->prepare('
                UPDATE users
                SET password_hash = ?, updated_at = NOW()
                WHERE email = ?
            ');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $reset['email']]);

            // Delete reset token
            $stmt = $this->db->prepare('DELETE FROM password_resets WHERE token = ?');
            $stmt->execute([$token]);

            $this->setFlash('success', 'Password reset successful! You can now log in.');
            $this->redirect('/login');

        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            $this->setFlash('error', 'An error occurred. Please try again.');
            $this->redirect('/reset-password?token=' . urlencode($token));
        }
    }

    /**
     * Verify email address
     */
    public function verifyEmail(): void
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $this->setFlash('error', 'Invalid verification token.');
            $this->redirect('/login');
            return;
        }

        try {
            $stmt = $this->db->prepare('
                UPDATE users
                SET email_verified_at = NOW(), verification_token = NULL, updated_at = NOW()
                WHERE verification_token = ? AND email_verified_at IS NULL
            ');
            $stmt->execute([$token]);

            if ($stmt->rowCount() > 0) {
                $this->setFlash('success', 'Email verified successfully! You can now log in.');
            } else {
                $this->setFlash('error', 'Invalid or expired verification token.');
            }

        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            $this->setFlash('error', 'An error occurred during verification.');
        }

        $this->redirect('/login');
    }

    /**
     * Resend verification email
     */
    public function resendVerification(): void
    {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setFlash('error', 'Please enter a valid email address.');
            $this->redirect('/login');
            return;
        }

        try {
            $stmt = $this->db->prepare('
                SELECT id FROM users
                WHERE email = ? AND email_verified_at IS NULL AND deleted_at IS NULL
            ');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $newToken = bin2hex(random_bytes(32));
                $stmt = $this->db->prepare('
                    UPDATE users
                    SET verification_token = ?, updated_at = NOW()
                    WHERE id = ?
                ');
                $stmt->execute([$newToken, $user['id']]);

                // In a real app, send verification email here
                // $this->sendVerificationEmail($email, $newToken);
            }

            // Always show success message for security
            $this->setFlash('success', 'If an unverified account with that email exists, we\'ve sent a new verification link.');

        } catch (Exception $e) {
            error_log("Resend verification error: " . $e->getMessage());
            $this->setFlash('error', 'An error occurred. Please try again.');
        }

        $this->redirect('/login');
    }

    /**
     * Check if IP/email is rate limited
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

        return $stmt->fetchColumn() >= 5; // Max 5 failed attempts in 15 minutes
    }

    /**
     * Record login attempt
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
        $stmt->execute([$userId, $token, $expiresAt]);

        // Set cookie
        setcookie('remember_token', $token, [
            'expires' => strtotime('+30 days'),
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}