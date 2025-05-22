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

// Validate required fields
if (!isset($_POST['room_number']) || empty($_POST['room_number'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Room number is required']);
    exit;
}

if (!isset($_POST['room_type_id']) || empty($_POST['room_type_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Room type is required']);
    exit;
}

if (!isset($_POST['floor']) || !is_numeric($_POST['floor'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Valid floor number is required']);
    exit;
}

if (!isset($_POST['price_per_night']) || !is_numeric($_POST['price_per_night'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Valid price per night is required']);
    exit;
}

if (!isset($_POST['capacity']) || !is_numeric($_POST['capacity'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Valid capacity is required']);
    exit;
}

// Get database connection
$conn = connectDB();

// Prepare and sanitize data
$id              = intval($_GET['id']);
$room_number     = $conn->real_escape_string($_POST['room_number']);
$room_type_id    = intval($_POST['room_type_id']);
$floor           = intval($_POST['floor']);
$status          = $conn->real_escape_string($_POST['status']);
$notes           = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
$price_per_night = floatval($_POST['price_per_night']);
$capacity        = intval($_POST['capacity']);

// Update room — note the correct table name and backticks
$sql = "
    UPDATE `Room`
       SET room_number     = '$room_number',
           room_type_id    = $room_type_id,
           floor           = $floor,
           status          = '$status',
           notes           = '$notes',
           price_per_night = $price_per_night,
           capacity        = $capacity
     WHERE room_id        = $id
";

if ($conn->query($sql)) {
    if ($conn->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
    } else {
        // No rows affected could mean ID doesn't exist or no changes
        echo json_encode(['success' => false, 'message' => 'Room not found or no changes made']);
    }
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error updating room: ' . $conn->error
    ]);
}

$conn->close();
?>