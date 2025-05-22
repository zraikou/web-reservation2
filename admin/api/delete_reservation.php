<?php
// Include database connection
require_once __DIR__ . '/../config/db_config.php';

// Error reporting for debugging
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Create connection
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
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg() . ' - Raw input: ' . substr($input, 0, 100));
    }
    
    if (!isset($data['reservation_id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Reservation ID is required']);
        exit;
    }
    
    $reservationId = intval($data['reservation_id']);
    
    // Delete reservation
    $query = "DELETE FROM Reservation WHERE reservation_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $reservationId);
    
    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Reservation deleted successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'warning',
                'message' => 'No reservation found with ID: ' . $reservationId
            ]);
        }
    } else {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
    
    // Close connection
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    // Log the error to server error log
    error_log('Delete reservation error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>