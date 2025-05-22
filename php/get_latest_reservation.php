<?php
// Include database configuration
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to view reservation details.'
    ]);
    exit();
}

$guestId = $_SESSION['guest_id'];

try {
    // Get PDO connection
    $pdo = getDBConnection();
    
    // Get the latest reservation for this guest with room details
    $stmt = $pdo->prepare("
        SELECT r.*, rm.room_number, rm.floor, rm.price_per_night,
        rt.name as display_name
        FROM Reservation r
        JOIN Room rm ON r.room_id = rm.room_id
        JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE r.guest_id = ?
        ORDER BY r.reservation_id DESC
        LIMIT 1
    ");
    $stmt->execute([$guestId]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reservation) {
        // Calculate nights for informational purposes
        $checkInDate = new DateTime($reservation['check_in']);
        $checkOutDate = new DateTime($reservation['check_out']);
        $interval = $checkInDate->diff($checkOutDate);
        $nights = $interval->days;
        
        echo json_encode([
            'success' => true,
            'reservation' => $reservation,
            'room_info' => [
                'display_name' => $reservation['display_name'],
                'room_number' => $reservation['room_number'],
                'floor' => $reservation['floor']
            ],
            'nights' => $nights,
            'price_per_night' => $reservation['price_per_night']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No reservation found.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>