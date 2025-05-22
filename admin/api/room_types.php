<?php
// 1) Turn on full error reporting (for development only!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);

// 2) CORS & preflight
header('Access-Control-Allow-Origin: https://techgrp.great-site.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-HTTP-Method-Override');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // CORS preflight; no need to process further
    http_response_code(204);
    exit;
}

// 3) Method override logic
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    // support both custom header override and form field
    if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
        $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
    } elseif (isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    }
}

// 4) For PUT/DELETE with form-encoded bodies, you may need to parse php://input
if (in_array($method, ['PUT','DELETE'])) {
    parse_str(file_get_contents('php://input'), $_REQUEST);
}
// Database connection configuration
require_once __DIR__ . '/../config/db_config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Create database connection
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

// Handle room types requests
$method = $_SERVER['REQUEST_METHOD'];
$conn = connectDB();

switch ($method) {
    case 'GET':
        // Get all room types
        $sql = "SELECT * FROM room_types ORDER BY name";
        $result = $conn->query($sql);
        
        if ($result) {
            $room_types = [];
            while ($row = $result->fetch_assoc()) {
                $room_types[] = $row;
            }
            echo json_encode($room_types);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['success' => false, 'message' => 'Error fetching room types']);
        }
        break;
        
    case 'POST':
        // Check if required data exists
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Room name is required']);
            break;
        }
        
        if (!isset($_POST['price_per_night']) || !is_numeric($_POST['price_per_night'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Valid price per night is required']);
            break;
        }
        
        if (!isset($_POST['capacity']) || !is_numeric($_POST['capacity'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Valid capacity is required']);
            break;
        }
        
        // Create new room type
        $name = $conn->real_escape_string($_POST['name']);
        $description = isset($_POST['description']) ? $conn->real_escape_string($_POST['description']) : '';
        $price_per_night = floatval($_POST['price_per_night']);
        $capacity = intval($_POST['capacity']);
        
        $sql = "INSERT INTO room_types (name, description, price_per_night, capacity) 
                VALUES ('$name', '$description', $price_per_night, $capacity)";
        
        if ($conn->query($sql)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Room type created successfully',
                'id' => $conn->insert_id
            ]);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['success' => false, 'message' => 'Error creating room type: ' . $conn->error]);
        }
        break;
        
    default:
        header('HTTP/1.1 405 Method Not Allowed');
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

$conn->close();
?>