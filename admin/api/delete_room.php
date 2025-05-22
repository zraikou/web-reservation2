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
    echo json_encode(['success' => false, 'message' => 'Room ID is required']);
    exit;
}

// Get database connection
$conn = connectDB();

$id = intval($_GET['id']);

// First check if there are reservations using this room
$check_sql = "SELECT COUNT(*) as count FROM Reservation WHERE room_id = $id";
$check_result = $conn->query($check_sql);
$row = $check_result->fetch_assoc();
if ($row['count'] > 0) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Cannot delete room. It is associated with ' . $row['count'] . ' reservation(s).']);
    exit;
}

// If no reservations are using this room, delete it
$sql = "DELETE FROM Room WHERE room_id = $id";

if ($conn->query($sql)) {
    // Check if any rows were affected
    if ($conn->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Room not found or already deleted']);
    }
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Error deleting room: ' . $conn->error]);
}

$conn->close();
?>