<?php
// Include database connection
require_once __DIR__ . '/../config/db_config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get reservation ID from query string
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Reservation ID is required']);
    exit;
}

$reservationId = intval($_GET['id']);

// Query to get reservation details
$query = "SELECT 
    r.reservation_id,
    r.guest_id,
    r.room_id,
    r.admin_id,
    r.check_in,
    r.check_out,
    r.total_price,
    r.status,
    g.first_name,
    g.last_name,
    g.email,
    g.phone,
    rm.room_number,
    rt.name as room_type,
    rt.price_per_night,
    rm.capacity
FROM Reservation r
JOIN Guest g ON r.guest_id = g.guest_id
JOIN Room rm ON r.room_id = rm.room_id
JOIN room_types rt ON rm.room_type_id = rt.id
WHERE r.reservation_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404); // Not Found
    echo json_encode(['status' => 'error', 'message' => 'Reservation not found']);
    $stmt->close();
    $conn->close();
    exit;
}

$reservation = $result->fetch_assoc();

// Format response data
$response = [
    'status' => 'success',
    'reservation' => [
        'id' => $reservation['reservation_id'],
        'guest' => [
            'id' => $reservation['guest_id'],
            'first_name' => $reservation['first_name'],
            'last_name' => $reservation['last_name'],
            'email' => $reservation['email'],
            'phone' => $reservation['phone']
        ],
        'room' => [
            'id' => $reservation['room_id'],
            'room_number' => $reservation['room_number'],
            'room_type' => $reservation['room_type'],
            'price_per_night' => $reservation['price_per_night'],
            'capacity' => $reservation['capacity']
        ],
        'admin_id' => $reservation['admin_id'],
        'check_in' => $reservation['check_in'],
        'check_out' => $reservation['check_out'],
        'total_price' => $reservation['total_price'],
        'status' => $reservation['status']
    ]
];

echo json_encode($response);

// Close connection
$stmt->close();
$conn->close();
?>