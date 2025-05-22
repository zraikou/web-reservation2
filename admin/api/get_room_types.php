<?php
// Get room types for dropdown
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once __DIR__ . '/../config/db_config.php';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Connection failed: ' . $conn->connect_error
    ]));
}

// Get room types for dropdown
$query = "SELECT id, name FROM room_types ORDER BY name";
$result = $conn->query($query);
$roomTypes = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $roomTypes[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
}

echo json_encode([
    'status' => 'success',
    'room_types' => $roomTypes
]);

// Close connection
$conn->close();
?>