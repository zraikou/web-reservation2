<?php
// Include database connection
require_once __DIR__ . '/../config/db_config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$requiredFields = ['guest_id', 'room_id', 'admin_id', 'check_in', 'check_out', 'total_price'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit;
}

// Extract data
$guestId = $data['guest_id'];
$roomId = $data['room_id'];
$adminId = $data['admin_id'];
$checkIn = $data['check_in'];
$checkOut = $data['check_out'];
$totalPrice = $data['total_price'];
$status = isset($data['status']) ? $data['status'] : 'confirmed';

// Validate date format
if (!DateTime::createFromFormat('Y-m-d', $checkIn) || !DateTime::createFromFormat('Y-m-d', $checkOut)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid date format. Use YYYY-MM-DD format.'
    ]);
    exit;
}

// Validate dates logic (check_out must be after check_in)
$checkInDate = new DateTime($checkIn);
$checkOutDate = new DateTime($checkOut);

if ($checkInDate >= $checkOutDate) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'message' => 'Check-out date must be after check-in date.'
    ]);
    exit;
}

// Check if room is available for the selected dates
$query = "SELECT reservation_id 
          FROM Reservation 
          WHERE room_id = ? 
          AND status NOT IN ('cancelled', 'checked_out')
          AND (
              (check_in BETWEEN ? AND ?) OR 
              (check_out BETWEEN ? AND ?) OR
              (check_in <= ? AND check_out >= ?)
          )";

$stmt = $conn->prepare($query);
$stmt->bind_param("issssss", $roomId, $checkIn, $checkOut, $checkIn, $checkOut, $checkIn, $checkOut);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'error',
        'message' => 'Room is not available for the selected dates.'
    ]);
    $stmt->close();
    exit;
}

// Insert new reservation
$query = "INSERT INTO Reservation (guest_id, room_id, admin_id, check_in, check_out, total_price, status) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiissds", $guestId, $roomId, $adminId, $checkIn, $checkOut, $totalPrice, $status);

if ($stmt->execute()) {
    $reservationId = $conn->insert_id;
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Reservation created successfully',
        'reservation_id' => $reservationId
    ]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create reservation: ' . $conn->error
    ]);
}

// Close connection
$stmt->close();
$conn->close();
?>