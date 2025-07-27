<?php
// auth/reset_password.php

$current_page = basename($_SERVER['PHP_SELF']);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.use_strict_mode', 1);

session_start();

// Define base URL path
define('WEB_BASE', 'http://localhost/Gamers_Community/');

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Include database connection
require_once __DIR__ . '/../config/db.php';

// Initialize variables
$error = '';
$success = '';
$valid_token = false;

// Check for token in URL
$token = $_GET['token'] ?? '';

if ($token) {
    // Verify token exists and isn't expired - CHANGED TO reset_token_expiry
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();
    
    $valid_token = ($stmt->num_rows === 1);
    $stmt->close();
}

// Handle password reset form
if ($_SERVER["REQUEST_METHOD"] === "POST" && $valid_token) {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "❌ Invalid request. Please try again.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate passwords
        if (strlen($password) < 8) {
            $error = "❌ Password must be at least 8 characters.";
        } elseif ($password !== $confirm_password) {
            $error = "❌ Passwords don't match.";
        } else {
            // Update password and clear reset token - CHANGED TO reset_token_expiry
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
            $update->bind_param("ss", $hashed_password, $token);
            
            if ($update->execute()) {
                $success = "✅ Password updated successfully. You can now <a href='login.php'>login</a> with your new password.";
                $valid_token = false; // Token has been used
            } else {
                $error = "❌ Error updating password. Please try again.";
            }
            $update->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Gamers Community</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Include same styles as forgot_password.php -->
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="main-content">
    <section class="form-section">
        <h2>Reset Your Password</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-msg"><?= $success ?></div>
        <?php elseif ($valid_token): ?>
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input id="password" name="password" type="password" required 
                           placeholder="Minimum 8 characters" minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required 
                           placeholder="Re-enter your password" minlength="8">
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>
        <?php else: ?>
            <div class="error-msg">
                Invalid or expired password reset link. Please request a <a href="forgot_password.php">new reset link</a>.
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>