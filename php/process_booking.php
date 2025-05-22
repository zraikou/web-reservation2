<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Include config file without starting the session again
require_once 'config.php';
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ensure user is logged in and has a guest_id
if (!isset($_SESSION['guest_id'])) {
    $_SESSION['reservation_errors'] = ['You must be logged in to make a reservation.'];
    header("Location: ../login.html");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $guestId = $_SESSION['guest_id'];
    $roomId = $_POST['room_id'] ?? null;
    $checkIn = $_POST['check_in'] ?? null;
    $checkOut = $_POST['check_out'] ?? null;
    // Optionally handle special requests - but don't save them as we don't have a field
    $specialRequests = $_POST['special_requests'] ?? '';
    
    $errors = [];
    if (!$roomId || !$checkIn || !$checkOut) {
        $errors[] = "All fields are required.";
    }
    if (!empty($errors)) {
        $_SESSION['reservation_errors'] = $errors;
        header("Location: ../index.html#booking");
        exit();
    }
    // Make sure $pdo is available and connected
    if (!isset($pdo) || $pdo === null) {
        // Connection might not be established in config.php
        // Fallback connection initialization
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $_SESSION['reservation_errors'] = ["Database connection failed: " . $e->getMessage()];
            header("Location: ../index.html#booking");
            exit();
        }
    }
    try {
        // Begin transaction for data integrity
        $pdo->beginTransaction();
        
        // Get room information to calculate total price
        // Also join with room_types to handle default price if room's price is 0
        $roomStmt = $pdo->prepare("
            SELECT r.room_id, r.price_per_night, rt.price_per_night as type_price 
            FROM Room r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.room_id = ?
        ");
        $roomStmt->execute([$roomId]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            throw new Exception("Selected room does not exist.");
        }
        
        // Use room-specific price if set, otherwise use the type's default price
        $roomPrice = ($room['price_per_night'] > 0) ? $room['price_per_night'] : $room['type_price'];
        
        // Calculate number of nights and total price
        $checkInDate = new DateTime($checkIn);
        $checkOutDate = new DateTime($checkOut);
        $interval = $checkInDate->diff($checkOutDate);
        $numNights = $interval->days;
        
        if ($numNights < 1) {
            throw new Exception("Check-out date must be after check-in date.");
        }
        
        $totalPrice = $roomPrice * $numNights;
        $adminId = null; // Placeholder if not yet implemented
        $status = 'pending'; // Changed from 'confirmed' to 'pending'
        
        // Check if room is available for the requested dates
        $availabilityStmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM Reservation 
            WHERE room_id = ? AND status IN ('pending', 'confirmed') 
            AND ((check_in <= ? AND check_out > ?) OR (check_in < ? AND check_out >= ?))
        ");
        $availabilityStmt->execute([$roomId, $checkOut, $checkIn, $checkOut, $checkIn]);
        $result = $availabilityStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            throw new Exception("This room is not available for the selected dates.");
        }
        
        // Insert reservation record
        $stmt = $pdo->prepare("
            INSERT INTO Reservation (guest_id, room_id, admin_id, check_in, check_out, total_price, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$guestId, $roomId, $adminId, $checkIn, $checkOut, $totalPrice, $status]);
        
        // Update room status to reserved (not occupied yet since it's pending)
        $updateRoomStmt = $pdo->prepare("UPDATE Room SET status = 'reserved' WHERE room_id = ?");
        $updateRoomStmt->execute([$roomId]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['reservation_success'] = true;
        header("Location: ../reservation-confirmation.html");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['reservation_errors'] = ["Reservation failed: " . $e->getMessage()];
        header("Location: ../index.html#booking");
        exit();
    }
}
?>