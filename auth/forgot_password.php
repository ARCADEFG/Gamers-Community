<?php
// auth/forgot_password.php

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

// Redirect to homepage if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . WEB_BASE . "index.php");
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$error = '';
$success = '';
$email = '';

// Handle POST form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "❌ Invalid request. Please try again.";
    } else {
        $email = $_POST['email'] ?? '';
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "❌ Invalid email format.";
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows === 1) {
                // Generate reset token (valid for 1 hour)
                $reset_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', time() + 3600);
                
                // Store token in database - updated field names
                $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
                $update->bind_param("sss", $reset_token, $expires_at, $email);
                
                if ($update->execute()) {
                    // In a real application, you would send an email here
                    // For this example, we'll just show the reset link
                    $reset_link = WEB_BASE . "auth/reset_password.php?token=" . urlencode($reset_token);
                    $success = "Password reset link generated. For demonstration: <a href='$reset_link'>Reset Password</a>";
                } else {
                    $error = "❌ Error generating reset link. Please try again.";
                }
                $update->close();
            } else {
                // Don't reveal if user exists
                $success = "If this email exists in our system, you'll receive a password reset link.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gamers Community - Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Your CSS -->
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
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
        <?php else: ?>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
            
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required 
                           placeholder="Your registered email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
                
                <div class="form-links">
                    <a href="<?= WEB_BASE ?>auth/login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>