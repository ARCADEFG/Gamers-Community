<?php
// Define base URL path
define('WEB_BASE', 'http://localhost/Gamers_Community/');
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $verify   = bin2hex(random_bytes(16));

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, verification_code) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $password, $verify);

    if ($stmt->execute()) {
        $message = "✅ Registration successful. Check your email for verification (simulate for now).";
        $messageClass = "alert-success";
    } else {
        $message = "❌ Error: " . $stmt->error;
        $messageClass = "alert-error";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Gamers Community</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Orbitron:wght@700;800&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Your CSS -->
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">

<style>
:root {
  --primary: #6a11cb;
  --secondary: #2575fc;
  --accent: #ff4081;
  --light: #f4f4f4;
  --dark-bg: #121212;
  --card-bg: #2b2b2b;
  --input-bg: #3a3a3a;
  --text-muted: #aaa;
}

body {
  margin: 0;
  font-family: 'Montserrat', sans-serif;
  background-color: var(--dark-bg);
  color: white;
}

.register-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 200px); /* Adjust based on header/footer height */
    padding: 2rem;
    background-color: var(--dark-bg);
}

.register-section {
    width: 100%;
    max-width: 500px;
    padding: 2.5rem;
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.register-section:hover {
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.4);
    transform: translateY(-5px);
}

.register-title {
    text-align: center;
    margin-bottom: 1.5rem;
    font-size: 2.2rem;
    font-weight: 700;
    background: linear-gradient(45deg, var(--primary), var(--secondary));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    letter-spacing: 1px;
    font-family: 'Orbitron', sans-serif;
}

.register-form {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
}

.form-group {
    position: relative;
    margin-bottom: 1rem;
}

.register-form input {
    width: 85%;
    padding: 1rem 1rem 1rem 3rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    background: var(--input-bg);
    color: #fff;
    transition: all 0.3s ease;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
    font-family: 'Montserrat', sans-serif;
}

.register-form input:focus {
    outline: 2px solid var(--accent);
    box-shadow: 0 0 0 4px rgba(255, 64, 129, 0.3),
                inset 0 1px 3px rgba(0, 0, 0, 0.2);
}

.register-form input::placeholder {
    color: var(--text-muted);
    opacity: 0.7;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 1.2rem;
}

.password-strength {
    margin-top: 0.5rem;
    height: 4px;
    background: #333;
    border-radius: 2px;
    overflow: hidden;
}

.password-strength-fill {
    height: 100%;
    width: 0%;
    transition: width 0.3s ease;
}

.password-hint {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 0.5rem;
    display: none;
}

.register-form input:focus + .input-icon {
    color: var(--accent);
}

.btn-register {
    background: linear-gradient(45deg, var(--primary), var(--secondary));
    color: white;
    padding: 1rem;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-family: 'Montserrat', sans-serif;
}

.btn-register:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(106, 17, 203, 0.4);
}

.btn-register:active {
    transform: translateY(0);
}

.form-footer {
    text-align: center;
    margin-top: 2rem;
    color: var(--text-muted);
    font-size: 0.95rem;
}

.form-footer a {
    color: var(--accent);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.form-footer a:hover {
    text-decoration: underline;
    color: var(--secondary);
}

.toggle-password {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 1.2rem;
    transition: color 0.2s;
}

.toggle-password:hover {
    color: var(--accent);
}

/* Alert messages */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: rgba(46, 204, 113, 0.15);
    color: #2ecc71;
    border-left: 4px solid #2ecc71;
}

.alert-error {
    background: rgba(231, 76, 60, 0.15);
    color: #e74c3c;
    border-left: 4px solid #e74c3c;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .register-container {
        padding: 1rem;
    }
    
    .register-section {
        padding: 1.8rem;
    }
    
    .register-title {
        font-size: 1.8rem;
    }
    
    .register-form input {
        padding: 0.9rem 0.9rem 0.9rem 2.8rem;
    }
}

@media (max-width: 480px) {
    .register-section {
        padding: 1.5rem;
    }
    
    .register-title {
        font-size: 1.6rem;
    }
    
    .btn-register {
        padding: 0.9rem;
        font-size: 1rem;
    }
}
</style>
   
</head>
<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

<main>
    <div class="register-container">
    <div class="register-section">
        <h2 class="register-title">Create Account</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageClass; ?>">
                <i class="fas <?php echo $messageClass === 'alert-success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="register-form">
            <div class="form-group">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" required placeholder="Username" value="<?php echo htmlspecialchars($username ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" required placeholder="Email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            
            <div class="form-group password-toggle-container">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" required placeholder="Password" id="password-field">
                <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                    <i class="fas fa-eye"></i>
                </button>
                <div class="password-strength">
                    <div class="password-strength-fill" id="password-strength-bar"></div>
                </div>
                <div class="password-hint" id="password-hint">
                    Use 8+ characters with mix of letters, numbers & symbols
                </div>
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password_confirm" required placeholder="Confirm Password" id="confirm-password-field">
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>
        
        <div class="form-footer">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</div>
</main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle visibility
            const togglePassword = document.querySelector('.toggle-password');
            const passwordField = document.getElementById('password-field');
            const confirmPasswordField = document.getElementById('confirm-password-field');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                confirmPasswordField.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
            
            // Password strength meter
            passwordField.addEventListener('input', function() {
                const strengthBar = document.getElementById('password-strength-bar');
                const hint = document.getElementById('password-hint');
                const password = this.value;
                let strength = 0;
                
                // Show hint on focus
                if (password.length > 0) {
                    hint.style.display = 'block';
                } else {
                    hint.style.display = 'none';
                }
                
                // Check password strength
                if (password.length >= 8) strength += 1;
                if (password.match(/[a-z]/)) strength += 1;
                if (password.match(/[A-Z]/)) strength += 1;
                if (password.match(/[0-9]/)) strength += 1;
                if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
                
                // Update strength bar
                const width = (strength / 5) * 100;
                strengthBar.style.width = width + '%';
                
                // Change color based on strength
                if (width < 40) {
                    strengthBar.style.background = '#ff4757'; // Weak (red)
                } else if (width < 70) {
                    strengthBar.style.background = '#ffa502'; // Medium (orange)
                } else {
                    strengthBar.style.background = '#2ed573'; // Strong (green)
                }
            });
        });
    </script>

</body>
</html>
