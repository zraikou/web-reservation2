<?php
// Include database configuration
require_once 'config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to view your bookings.'
    ]);
    exit();
}

// Get guest ID from session
$guestId = $_SESSION['guest_id'];

try {
    // Get PDO connection
    $pdo = getDBConnection();
    
    // Get all reservations for this guest with room details
    $stmt = $pdo->prepare("
        SELECT r.reservation_id, r.check_in, r.check_out, r.total_price, r.status,
               rm.room_id, rm.room_number, rm.floor, rm.price_per_night,
               rt.name as room_type_name
        FROM Reservation r
        JOIN Room rm ON r.room_id = rm.room_id
        JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE r.guest_id = ?
        ORDER BY r.reservation_id DESC
    ");
    
    $stmt->execute([$guestId]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($reservations) > 0) {
        // Process each reservation
        $processedReservations = [];
        
        foreach ($reservations as $reservation) {
            // Calculate nights for informational purposes
            $checkInDate = new DateTime($reservation['check_in']);
            $checkOutDate = new DateTime($reservation['check_out']);
            $interval = $checkInDate->diff($checkOutDate);
            $nights = $interval->days;
            
            // Add to processed reservations array
            $processedReservations[] = [
                'reservation_id' => $reservation['reservation_id'],
                'check_in' => $reservation['check_in'],
                'check_out' => $reservation['check_out'],
                'total_price' => $reservation['total_price'],
                'status' => $reservation['status'],
                'room_number' => $reservation['room_number'],
                'floor' => $reservation['floor'],
                'room_type_name' => $reservation['room_type_name'],
                'price_per_night' => $reservation['price_per_night'],
                'nights' => $nights
            ];
        }
        
        echo json_encode([
            'success' => true,
            'reservations' => $processedReservations
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