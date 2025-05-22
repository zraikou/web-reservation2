<?php
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

// Handle rooms requests
$method = $_SERVER['REQUEST_METHOD'];
$conn = connectDB();

switch ($method) {
    case 'GET':
        // Get all rooms with room type information
        $sql = "SELECT r.*, rt.name as room_type_name 
                FROM Room r 
                LEFT JOIN room_types rt ON r.room_type_id = rt.id 
                ORDER BY r.room_number";
        $result = $conn->query($sql);
        
        if ($result) {
            $rooms = [];
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }
            echo json_encode($rooms);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['success' => false, 'message' => 'Error fetching rooms']);
        }
        break;
        
    case 'POST':
        // Create new room
        $room_number = $conn->real_escape_string($_POST['room_number']);
        $room_type_id = intval($_POST['room_type_id']);
        $floor = intval($_POST['floor']);
        $status = $conn->real_escape_string($_POST['status']);
        $notes = $conn->real_escape_string($_POST['notes'] ?? '');
        $price_per_night = floatval($_POST['price_per_night']);
        $capacity = intval($_POST['capacity']);
        
        // Check if room number already exists
        $check_sql = "SELECT COUNT(*) as count FROM Room WHERE room_number = '$room_number'";
        $check_result = $conn->query($check_sql);
        $row = $check_result->fetch_assoc();
        
        if ($row['count'] > 0) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Room number already exists']);
            break;
        }
        
        $sql = "INSERT INTO Room (room_number, room_type_id, floor, status, notes, price_per_night, capacity) 
                VALUES ('$room_number', $room_type_id, $floor, '$status', '$notes', $price_per_night, $capacity)";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Room created successfully']);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['success' => false, 'message' => 'Error creating room: ' . $conn->error]);
        }
        break;
        
    case 'PUT':
        // Update room
        if (!isset($_GET['id'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Room ID is required']);
            break;
        }
        
        // Get PUT data
        parse_str(file_get_contents("php://input"), $_PUT);
        
        $id = intval($_GET['id']);
        $room_number = $conn->real_escape_string($_PUT['room_number']);
        $room_type_id = intval($_PUT['room_type_id']);
        $floor = intval($_PUT['floor']);
        $status = $conn->real_escape_string($_PUT['status']);
        $notes = $conn->real_escape_string($_PUT['notes'] ?? '');
        $price_per_night = floatval($_PUT['price_per_night']);
        $capacity = intval($_PUT['capacity']);
        
        // Check if room number already exists (excluding current room)
        $check_sql = "SELECT COUNT(*) as count FROM Room WHERE room_number = '$room_number' AND room_id != $id";
        $check_result = $conn->query($check_sql);
        $row = $check_result->fetch_assoc();
        
        if ($row['count'] > 0) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Room number already exists']);
            break;
        }
        
        $sql = "UPDATE Room 
                SET room_number = '$room_number', 
                    room_type_id = $room_type_id, 
                    floor = $floor, 
                    status = '$status', 
                    notes = '$notes', 
                    price_per_night = $price_per_night, 
                    capacity = $capacity 
                WHERE room_id = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['success' => false, 'message' => 'Error updating room: ' . $conn->error]);
        }
        break;
        
    case 'DELETE':
        // Delete room
        if (!isset($_GET['id'])) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'message' => 'Room ID is required']);
            break;
        }
        
        $id = intval($_GET['id']);
        
        // First check if there are bookings for this room
        $check_sql = "SELECT COUNT(*) as count FROM bookings WHERE room_id = $id";
        $check_result = $conn->query($check_sql);
        
        // Only check if the bookings table exists
        if ($check_result) {
            $row = $check_result->fetch_assoc();
            
            if ($row['count'] > 0) {
                header('HTTP/1.1 400 Bad Request');
                echo json_encode(['success' => false, 'message' => 'Cannot delete room. It has ' . $row['count'] . ' booking(s).']);
                break;
            }
        }
        
        // If no bookings are using this room, delete it
        $sql = "DELETE FROM Room WHERE room_id = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['success' => false, 'message' => 'Error deleting room: ' . $conn->error]);
        }
        break;
        
    default:
        header('HTTP/1.1 405 Method Not Allowed');
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

$conn->close();
?>