<?php
// config/config.php

/**
 * Gamers Community Configuration File
 * 
 * This file should be included at the top of every PHP page.
 * It handles:
 * - Path definitions
 * - Session management
 * - Database connection
 * - Error reporting settings
 */

// ========================
// 1. PATH CONFIGURATION
// ========================

// Define filesystem root path (points to C:\xampp\htdocs\Gamers_Community)
define('ROOT_PATH', dirname(__DIR__));

// Define web-accessible base URL (with trailing slash)
define('WEB_BASE', '/Gamers_Community/');

// Define important directory paths
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('PROFILE_PICS_PATH', UPLOADS_PATH . '/profile_pics');
define('ASSETS_PATH', WEB_BASE . 'assets');

// ========================
// 2. SESSION MANAGEMENT
// ========================

// Ensure sessions are properly started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 day
        'cookie_secure'   => false,  // Should be true in production with HTTPS
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// ========================
// 3. DATABASE CONNECTION
// ========================

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gamers_community');

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("DB Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        die("Database Error: " . $e->getMessage());
    } else {
        die("We're experiencing technical difficulties. Please try again later.");
    }
}

// Make connection available globally
$GLOBALS['conn'] = $conn;

// ========================
// 4. ERROR REPORTING
// ========================

// Development environment settings
define('ENVIRONMENT', 'development'); // Change to 'production' when live

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ========================
// 5. SECURITY SETTINGS
// ========================

// CSRF Token - generate if doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ========================
// 6. FILE UPLOAD SETTINGS
// ========================

// Ensure upload directories exist
if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
}

if (!file_exists(PROFILE_PICS_PATH)) {
    mkdir(PROFILE_PICS_PATH, 0755, true);
}

// ========================
// 7. GLOBAL FUNCTIONS
// ========================

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Generate absolute URL
 */
function url($path = '') {
    return WEB_BASE . ltrim($path, '/');
}

/**
 * Generate path to asset
 */
function asset($path) {
    return ASSETS_PATH . ltrim($path, '/');
}

/**
 * Redirect with optional status code
 */
function redirect($url, $statusCode = 303) {
    header('Location: ' . url($url), true, $statusCode);
    exit();
}

/**
 * Sanitize output
 */
function sanitize($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}