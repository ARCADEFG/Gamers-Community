<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Set session configuration BEFORE starting session
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

// Include database connection using absolute path
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
$login_attempts = $_SESSION['login_attempts'] ?? 0;

// Handle POST login form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check rate limiting
    if ($login_attempts > 5) {
        $error = "❌ Too many login attempts. Please try again in 30 minutes.";
    } else {
        // Validate CSRF token
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
            $error = "❌ Invalid request. Please try again.";
            error_log("CSRF token validation failed for login attempt");
        } else {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "❌ Invalid email format.";
            } else {
                $stmt = $conn->prepare("SELECT id, username, password, avatar FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($id, $username, $hashed, $avatar);
                    $stmt->fetch();
                    
                    if (password_verify($password, $hashed)) {
                        // Successful login - reset attempts
                        $_SESSION['user_id'] = $id;
                        $_SESSION['username'] = $username;
                        $_SESSION['avatar'] = $avatar ?? 'default.png';  // From database or default
                        
                        // Regenerate session ID to prevent fixation
                        session_regenerate_id(true);
                        
                        header("Location: " . WEB_BASE . "index.php");
                        exit;
                    } else {
                        // Increment failed attempts
                        $_SESSION['login_attempts'] = $login_attempts + 1;
                        error_log("Failed login attempt for email: " . $email);
                        $error = "❌ Invalid email or password.";
                    }
                } else {
                    // Don't reveal if user exists
                    $_SESSION['login_attempts'] = $login_attempts + 1;
                    error_log("Failed login attempt for email: " . $email);
                    $error = "❌ Invalid email or password.";
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gamers Community - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Your CSS -->
    <link rel="stylesheet" href="/Gamers_Community/assets/css/style.css">
    
<style>
.form-section {
  max-width: 400px;
  margin: 3em auto;
  background: var(--card-bg);
  padding: 2em;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
  text-align: center;
  transition: transform 0.3s;
}

.form-section:hover {
  transform: translateY(-3px);
}

.login-form input {
  width: 90%;
  padding: 0.8em;
  margin: 0.5em 0;
  border: none;
  border-radius: 4px;
  font-size: 1em;
  background: var(--input-bg);
  color: #fff;
  transition: box-shadow 0.2s;
}

.login-form input:focus {
  outline: 2px solid var(--accent);
  box-shadow: 0 0 0 4px rgba(255, 64, 129, 0.3);
}

.error-msg {
    color: #ff6b6b;
    margin-bottom: 1em;
    padding: 0.8em;
    background: rgba(255, 0, 0, 0.1);
    border-radius: 4px;
}

.warning-msg {
    color: #feca57;
    margin-bottom: 1em;
    padding: 0.8em;
    background: rgba(254, 202, 87, 0.1);
    border-radius: 4px;
}

/* Password Input with Toggle */
.password-input-container {
    position: relative;
    margin: 0.5em 0;
}

.password-input {
    padding-right: 40px;
    width: 100%;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #777;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.2s;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
}

.password-toggle:hover {
    color: var(--accent);
    background: rgba(255, 255, 255, 0.1);
}

.password-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(74, 107, 255, 0.3);
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    margin: 1.5em 0;
    flex-wrap: wrap;
}

.btn-login {
    background: var(--accent);
    color: #fff;
    padding: 0.8em;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
    flex: 1;
    min-width: 120px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-login:hover {
    background: var(--primary);
    transform: translateY(-1px);
}

.btn-login.loading {
  position: relative;
  color: transparent;
}

.btn-login.loading::after {
  content: "";
  position: absolute;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-forgot {
    background: #f39c12;
    color: white;
    padding: 0.8em;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
    flex: 1;
    min-width: 120px;
    transition: all 0.3s;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-forgot:hover {
    background: #e67e22;
    transform: translateY(-1px);
}

/* Form Links */
.form-links {
    text-align: center;
    margin-top: 1em;
    color: var(--text-muted);
    font-size: 0.9em;
}

.form-links a {
    color: var(--accent);
    text-decoration: none;
    transition: color 0.2s;
}

.form-links a:hover {
    text-decoration: underline;
}

/* Responsive adjustments */
@media (max-width: 480px) {
    .form-section {
        margin: 1.5em;
        padding: 1.5em;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-login {
        width: 100%;
        flex: none;
    }

    .btn-forgot {
        width: 90%;
        flex: none;
    }
}
</style>
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="main-content">
    <section class="form-section">
        <h2>Login to Gamers Community</h2>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($login_attempts > 3): ?>
            <div class="warning-msg">
                <i class="fas fa-exclamation-triangle"></i>
                You've had <?= $login_attempts ?> failed login attempts.
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required 
                       placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input-container">
                <input id="password" name="password" type="password" required 
                    placeholder="Password" minlength="8" class="password-input">
                    <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <a href="<?= WEB_BASE ?>auth/forgot_password.php" class="btn-forgot">
                    <i class="fas fa-question-circle"></i> Forgot Password?
                </a>
            </div>
            
            <div class="form-links">
                <span>Don't have an account? <a href="<?= WEB_BASE ?>auth/register.php">Register here</a></span>
            </div>
        </form>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Password visibility toggle
document.querySelector('.password-toggle').addEventListener('click', function() {
    const passwordInput = document.querySelector('.password-input');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
        this.setAttribute('aria-label', 'Hide password');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
        this.setAttribute('aria-label', 'Show password');
    }
});

// Add basic client-side validation
document.querySelector('.login-form').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (!email.includes('@')) {
        alert('Please enter a valid email address');
        e.preventDefault();
        return false;
    }
    
    if (password.length < 8) {
        alert('Password must be at least 8 characters');
        e.preventDefault();
        return false;
    }
    
    return true;
});
</script>

</body>
</html>
