<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT id, name, description, price_per_night, capacity FROM room_types ORDER BY price_per_night ASC");
    $stmt->execute();
    
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'rooms' => $rooms]);
    
} catch (PDOException $e) {
    handleError("Database error: " . $e->getMessage(), 500);
}
?>