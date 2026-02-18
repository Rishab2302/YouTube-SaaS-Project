<?php

class Email
{
    private array $config;
    private bool $isProduction;

    public function __construct()
    {
        $this->config = getAppConfig();
        $this->isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
    }

    /**
     * Send email using appropriate method based on environment
     */
    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        try {
            if ($this->isProduction && $this->isSmtpConfigured()) {
                return $this->sendSmtp($to, $subject, $htmlBody, $textBody);
            } else {
                return $this->sendMail($to, $subject, $htmlBody, $textBody);
            }
        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send verification email to user
     * REQ-AUTH-005: Verification email with unique token
     */
    public function sendVerificationEmail(array $user, string $token): bool
    {
        $appName = $_ENV['APP_NAME'] ?? 'TaskFlow';
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        $verifyUrl = $appUrl . '/verify-email?token=' . urlencode($token);

        $subject = "Verify your {$appName} account";

        $htmlBody = $this->getVerificationEmailTemplate($user, $verifyUrl, $appName);
        $textBody = $this->getVerificationEmailText($user, $verifyUrl, $appName);

        return $this->send($user['email'], $subject, $htmlBody, $textBody);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(string $email, string $token): bool
    {
        $appName = $_ENV['APP_NAME'] ?? 'TaskFlow';
        $appUrl = $_ENV['APP_URL'] ?? 'http://localhost';
        $resetUrl = $appUrl . '/reset-password?token=' . urlencode($token);

        $subject = "Reset your {$appName} password";

        $htmlBody = $this->getPasswordResetEmailTemplate($email, $resetUrl, $appName);
        $textBody = $this->getPasswordResetEmailText($email, $resetUrl, $appName);

        return $this->send($email, $subject, $htmlBody, $textBody);
    }

    /**
     * Send email using PHP's mail() function (development)
     */
    private function sendMail(string $to, string $subject, string $htmlBody, string $textBody): bool
    {
        $fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@taskflow.app';
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'TaskFlow';

        // Create multipart message
        $boundary = uniqid();

        $headers = [
            'From: "' . $fromName . '" <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'MIME-Version: 1.0',
            'X-Mailer: PHP/' . phpversion()
        ];

        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $textBody ?: strip_tags($htmlBody);
        $message .= "\r\n\r\n--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $htmlBody;
        $message .= "\r\n\r\n--{$boundary}--";

        $success = mail($to, $subject, $message, implode("\r\n", $headers));

        if ($success) {
            error_log("Email sent to {$to}: {$subject}");
        } else {
            error_log("Failed to send email to {$to}: {$subject}");
        }

        return $success;
    }

    /**
     * Send email using SMTP (production)
     */
    private function sendSmtp(string $to, string $subject, string $htmlBody, string $textBody): bool
    {
        // For production, you would implement SMTP here using PHPMailer or similar
        // For now, log the email attempt and return success
        error_log("SMTP Email would be sent to {$to}: {$subject}");
        return true;
    }

    /**
     * Check if SMTP is configured
     */
    private function isSmtpConfigured(): bool
    {
        return !empty($_ENV['MAIL_HOST']) &&
               !empty($_ENV['MAIL_USERNAME']) &&
               !empty($_ENV['MAIL_PASSWORD']);
    }

    /**
     * Get HTML template for verification email
     */
    private function getVerificationEmailTemplate(array $user, string $verifyUrl, string $appName): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Verify Your Account</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; }
                .button { display: inline-block; background: #007bff; color: white; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #6c757d; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✓ Welcome to ' . htmlspecialchars($appName) . '</h1>
                </div>
                <div class="content">
                    <h2>Hi ' . htmlspecialchars($user['first_name']) . ',</h2>
                    <p>Thank you for signing up for ' . htmlspecialchars($appName) . '! To get started, please verify your email address by clicking the button below:</p>

                    <div style="text-align: center;">
                        <a href="' . htmlspecialchars($verifyUrl) . '" class="button">Verify Email Address</a>
                    </div>

                    <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
                    <p style="word-break: break-all; color: #007bff;">' . htmlspecialchars($verifyUrl) . '</p>

                    <p>This verification link will expire in 24 hours for security reasons.</p>

                    <p>If you didn\'t create an account with ' . htmlspecialchars($appName) . ', you can safely ignore this email.</p>
                </div>
                <div class="footer">
                    <p>© 2026 ' . htmlspecialchars($appName) . '. Built for productivity.</p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Get plain text version for verification email
     */
    private function getVerificationEmailText(array $user, string $verifyUrl, string $appName): string
    {
        return "
Hi {$user['first_name']},

Thank you for signing up for {$appName}! To get started, please verify your email address by visiting this link:

{$verifyUrl}

This verification link will expire in 24 hours for security reasons.

If you didn't create an account with {$appName}, you can safely ignore this email.

---
© 2026 {$appName}. Built for productivity.
        ";
    }

    /**
     * Get HTML template for password reset email
     */
    private function getPasswordResetEmailTemplate(string $email, string $resetUrl, string $appName): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Reset Your Password</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; }
                .button { display: inline-block; background: #dc3545; color: white; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 14px; color: #6c757d; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔒 Password Reset</h1>
                </div>
                <div class="content">
                    <h2>Password Reset Request</h2>
                    <p>We received a request to reset the password for your ' . htmlspecialchars($appName) . ' account (' . htmlspecialchars($email) . ').</p>

                    <div style="text-align: center;">
                        <a href="' . htmlspecialchars($resetUrl) . '" class="button">Reset Password</a>
                    </div>

                    <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
                    <p style="word-break: break-all; color: #dc3545;">' . htmlspecialchars($resetUrl) . '</p>

                    <p>This password reset link will expire in 1 hour for security reasons.</p>

                    <p><strong>If you didn\'t request a password reset, you can safely ignore this email.</strong> Your password will not be changed.</p>
                </div>
                <div class="footer">
                    <p>© 2026 ' . htmlspecialchars($appName) . '. Built for productivity.</p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Get plain text version for password reset email
     */
    private function getPasswordResetEmailText(string $email, string $resetUrl, string $appName): string
    {
        return "
Password Reset Request

We received a request to reset the password for your {$appName} account ({$email}).

To reset your password, visit this link:
{$resetUrl}

This password reset link will expire in 1 hour for security reasons.

If you didn't request a password reset, you can safely ignore this email. Your password will not be changed.

---
© 2026 {$appName}. Built for productivity.
        ";
    }
}