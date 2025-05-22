<?php
// update_booking_status.php - Updates booking status based on action
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once __DIR__ . '/../config/db_config.php';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get JSON data from request body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!isset($data['id']) || !isset($data['action'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$reservationId = (int)$data['id'];
$action = strtolower($data['action']);

// Determine new status based on action
$newStatus = '';
$roomStatus = '';

switch ($action) {
    case 'check-in':
        $newStatus = 'checked_in';
        $roomStatus = 'occupied';
        break;
    
    case 'check-out':
        $newStatus = 'checked_out';
        $roomStatus = 'available';
        break;
    
    case 'cancel':
        $newStatus = 'cancelled';
        $roomStatus = 'available';
        break;
    
    case 'confirm':
        $newStatus = 'confirmed';
        // Room status remains the same on confirmation
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action: ' . $action]);
        exit;
}

// Simple approach without transaction for debugging
// Update reservation status
$updateReservationQuery = "UPDATE Reservation SET status = ? WHERE reservation_id = ?";
$stmt = $conn->prepare($updateReservationQuery);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error preparing statement: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("si", $newStatus, $reservationId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error updating reservation: ' . $stmt->error
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

// If action affects room availability, update room status
if ($roomStatus !== '') {
    // First get the room_id for this reservation
    $getRoomQuery = "SELECT room_id FROM Reservation WHERE reservation_id = ?";
    $roomStmt = $conn->prepare($getRoomQuery);
    
    if (!$roomStmt) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error preparing room query: ' . $conn->error
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $roomStmt->bind_param("i", $reservationId);
    
    if (!$roomStmt->execute()) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error fetching room_id: ' . $roomStmt->error
        ]);
        $roomStmt->close();
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $result = $roomStmt->get_result();
    $room = $result->fetch_assoc();
    
    if (!$room) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Room not found for reservation: ' . $reservationId
        ]);
        $roomStmt->close();
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $roomId = $room['room_id'];
    $roomStmt->close();
    
    // Update room status
    $updateRoomQuery = "UPDATE Room SET status = ? WHERE room_id = ?";
    $roomUpdateStmt = $conn->prepare($updateRoomQuery);
    
    if (!$roomUpdateStmt) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error preparing room update: ' . $conn->error
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $roomUpdateStmt->bind_param("si", $roomStatus, $roomId);
    
    if (!$roomUpdateStmt->execute()) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Error updating room status: ' . $roomUpdateStmt->error
        ]);
        $roomUpdateStmt->close();
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $roomUpdateStmt->close();
}

// Return success
echo json_encode([
    'status' => 'success',
    'message' => 'Booking status updated successfully',
    'new_status' => $newStatus,
    'reservation_id' => $reservationId
]);

// Close connection
$stmt->close();
$conn->close();
?>