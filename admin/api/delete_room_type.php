<?php
// Turn on error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);

// Handle CORS
header('Access-Control-Allow-Origin: https://techgrp.great-site.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Set headers for JSON response
header('Content-Type: application/json');

// Database connection configuration
require_once __DIR__ . '/../config/db_config.php';

// Connect to database
function connectDB() {
    global $db_host, $db_user, $db_password, $db_name;
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
    if ($conn->connect_error) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    return $conn;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

// Check if ID exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Room type ID is required']);
    exit;
}

// Get database connection
$conn = connectDB();
$id = intval($_GET['id']);

// First check if there are rooms using this room type
$check_sql = "SELECT COUNT(*) as count FROM Room WHERE room_type_id = $id";
$check_result = $conn->query($check_sql);
$row = $check_result->fetch_assoc();
if ($row['count'] > 0) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Cannot delete room type. It is being used by ' . $row['count'] . ' room(s).']);
    exit;
}

// If no rooms are using this room type, delete it
$sql = "DELETE FROM room_types WHERE id = $id";
if ($conn->query($sql)) {
    // Check if any rows were affected
    if ($conn->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Room type deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Room type not found or already deleted']);
    }
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Error deleting room type: ' . $conn->error]);
}

$conn->close();
?>