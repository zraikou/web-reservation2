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

// Validate required fields
if (!isset($_POST['name']) || empty($_POST['name'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Room name is required']);
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
$id = intval($_GET['id']);
$name = $conn->real_escape_string($_POST['name']);
$description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
$price_per_night = floatval($_POST['price_per_night']);
$capacity = intval($_POST['capacity']);

// Update room type
$sql = "UPDATE room_types 
        SET name = '$name', 
            description = '$description', 
            price_per_night = $price_per_night, 
            capacity = $capacity 
        WHERE id = $id";

if ($conn->query($sql)) {
    // Check if any rows were affected
    if ($conn->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Room type updated successfully']);
    } else {
        // No rows affected could mean ID doesn't exist
        echo json_encode(['success' => false, 'message' => 'Room type not found or no changes made']);
    }
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Error updating room type: ' . $conn->error]);
}

$conn->close();
?>