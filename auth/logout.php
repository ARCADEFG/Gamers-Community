<?php
// auth/logout.php

// Define WEB_BASE if not already defined
if (!defined('WEB_BASE')) {
    // Adjust this to match your exact project folder name in htdocs
    define('WEB_BASE', '/Gamers_Community/');
    // For absolute certainty in all environments:
    // define('WEB_BASE', 'http://localhost/Gamers_Community/');
}

// 1. Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. CSRF Protection (works for both GET and POST)
$request_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $request_token ?? '')) {
    http_response_code(403);
    die('Invalid CSRF token');
}

// 3. Logging (before destroying session)
if (isset($_SESSION['user_id'])) {
    $log_message = sprintf(
        "Logout: UserID=%s, IP=%s, Time=%s",
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'],
        date('Y-m-d H:i:s')
    );
    error_log($log_message);
}

// 4. Regenerate session ID to prevent fixation
session_regenerate_id(true);

// 5. Clear all session data
$_SESSION = [];

// 6. Destroy the session cookie completely
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 7. Destroy the session
session_destroy();

// 8. Security headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// 9. HSTS Header (only enable if using HTTPS)
// header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");

// 10. Small security delay (helps prevent brute force)
usleep(250000); // Reduced to 0.25s for better UX

// 11. Robust redirect handling
$base_path = rtrim(WEB_BASE, '/');
$redirect_url = $base_path . '/index.php';

// Ensure proper URL format (handle both relative and absolute)
if (strpos(WEB_BASE, 'http') === 0) {
    // Absolute URL
    header("Location: $redirect_url");
} else {
    // Relative URL - prepend server info
    $host = $_SERVER['HTTP_HOST'];
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $protocol = $is_https ? 'https://' : 'http://';
    header("Location: $protocol$host$redirect_url");
}

exit;
?>