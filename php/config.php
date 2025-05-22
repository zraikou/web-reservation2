<?php
session_start();

// Database configuration
define('DB_HOST', 'sql107.infinityfree.com');  // Using the standard InfinityFree MySQL hostname
define('DB_USER', 'if0_38689249');             // Your FTP username
define('DB_PASS', 'T3vcyCiQPzxcIvr');         // Your FTP password
define('DB_NAME', 'if0_38689249_hotel_db');            // Database name (usually same as username)

// Create database connection
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['guest_id']);
}

// Function to redirect with message
function redirectWith($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $url");
    exit();
}

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Error handling function
function handleError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit();
}

// Initialize session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
