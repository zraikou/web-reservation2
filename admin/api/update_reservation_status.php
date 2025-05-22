<?php
// Include database connection
require_once __DIR__ . '/../config/db_config.php';
// Set headers for JSON response
header('Content-Type: application/json');
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);
// Check connection
if ($conn->connect_error) {
throw new Exception('Connection failed: ' . $conn->connect_error);
}
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 http_response_code(405); // Method Not Allowed
echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
exit;
}
// Get input data
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['reservation_id']) || !isset($data['status'])) {
 http_response_code(400); // Bad Request
echo json_encode(['status' => 'error', 'message' => 'Required parameters missing']);
exit;
}
$reservationId = $data['reservation_id'];
$status = $data['status'];
// Validate status - added 'pending' to valid statuses
$validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
if (!in_array($status, $validStatuses)) {
 http_response_code(400); // Bad Request
echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
exit;
}
// Start transaction to ensure data consistency
$conn->begin_transaction();
try {
// Update reservation status
$query = "UPDATE Reservation SET status = ? WHERE reservation_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $status, $reservationId);
if (!$stmt->execute()) {
throw new Exception("Failed to update reservation status: " . $conn->error);
 }
// Get room_id for this reservation
$roomQuery = "SELECT room_id FROM Reservation WHERE reservation_id = ?";
$roomStmt = $conn->prepare($roomQuery);
$roomStmt->bind_param("i", $reservationId);
if (!$roomStmt->execute()) {
throw new Exception("Failed to fetch room information: " . $conn->error);
 }
$result = $roomStmt->get_result();
if ($row = $result->fetch_assoc()) {
$roomId = $row['room_id'];
// Determine room status based on reservation status
$roomStatus = null;
switch ($status) {
case 'pending':
$roomStatus = 'reserved';
break;
case 'confirmed':
$roomStatus = 'occupied';
break;
case 'checked_in':
$roomStatus = 'occupied';
break;
case 'checked_out':
case 'cancelled':
$roomStatus = 'available';
break;
 }
// Update room status if needed
if ($roomStatus !== null) {
$roomUpdateQuery = "UPDATE Room SET status = ? WHERE room_id = ?";
$roomUpdateStmt = $conn->prepare($roomUpdateQuery);
$roomUpdateStmt->bind_param("si", $roomStatus, $roomId);
if (!$roomUpdateStmt->execute()) {
throw new Exception("Failed to update room status: " . $conn->error);
 }
$roomUpdateStmt->close();
 }
 }
$roomStmt->close();
$stmt->close();
// Commit the transaction
$conn->commit();
echo json_encode([
'status' => 'success',
'message' => 'Reservation status updated successfully'
 ]);
} catch (Exception $e) {
// Rollback in case of error
$conn->rollback();
 http_response_code(500); // Internal Server Error
echo json_encode([
'status' => 'error',
'message' => $e->getMessage()
 ]);
}
// Close connection
$conn->close();
?>