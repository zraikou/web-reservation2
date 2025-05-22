<?php
// get_rooms.php
header('Content-Type: application/json');
require_once 'config.php';

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure $pdo is available
if (!isset($pdo) || $pdo === null) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit();
    }
}

try {
    // Query joins Room and room_types tables to get all necessary information
    $stmt = $pdo->prepare("
        SELECT r.room_id, r.room_number, r.floor, r.status, r.price_per_night,
               r.capacity, rt.id as type_id, rt.name, rt.description,
               rt.price_per_night as type_price, rt.capacity as type_capacity
        FROM Room r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE r.status = 'available'
        ORDER BY r.room_id
    ");
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format rooms for display
    foreach($rooms as &$room) {
        // Use room-specific price if set, otherwise use the type's default price
        $room['display_price'] = ($room['price_per_night'] > 0) ? $room['price_per_night'] : $room['type_price'];
        
        // Use room-specific capacity if set, otherwise use the type's default capacity
        $room['display_capacity'] = ($room['capacity'] > 0) ? $room['capacity'] : $room['type_capacity'];
    }
    
    echo json_encode(['success' => true, 'rooms' => $rooms]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to fetch rooms: ' . $e->getMessage()]);
}
?>