<?php
// dashboard_summary.php - Provides summary data for dashboard cards
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
// Today's date
$today = date('Y-m-d');
// 1. Get available rooms count
$roomsQuery = "SELECT
    COUNT(*) AS total_rooms,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available_rooms
    FROM Room";
$roomsResult = $conn->query($roomsQuery);
if (!$roomsResult) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching room data: ' . $conn->error
    ]);
    $conn->close();
    exit;
}
$roomsData = $roomsResult->fetch_assoc();

// 2. Get today's check-ins count - people who have already checked in today
$checkInsQuery = "SELECT COUNT(*) AS todays_checkins
    FROM Reservation
    WHERE check_in = ? AND status = 'checked_in'";
$stmt = $conn->prepare($checkInsQuery);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error preparing check-ins query: ' . $conn->error
    ]);
    $conn->close();
    exit;
}
$stmt->bind_param("s", $today);
$stmt->execute();
$checkInsResult = $stmt->get_result();
$checkInsData = $checkInsResult->fetch_assoc();
$stmt->close();

// Return combined data
echo json_encode([
    'status' => 'success',
    'total_rooms' => (int)$roomsData['total_rooms'],
    'available_rooms' => (int)$roomsData['available_rooms'],
    'todays_checkins' => (int)$checkInsData['todays_checkins']
]);
// Close connection
$conn->close();
?>