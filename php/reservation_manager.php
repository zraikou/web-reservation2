<?php
require_once 'config.php';
require_once 'auth.php';

class ReservationManager {
    private $conn;
    private $auth;
    
    public function __construct() {
        $this->conn = connectDB();
        $this->auth = new Auth();
    }
    
    // Get all reservations
    public function getAllReservations() {
        $stmt = $this->conn->prepare("
            SELECT r.*, 
                   g.full_name, g.email, g.phone,
                   rm.room_number,
                   rt.name as room_type
            FROM Reservation r
            JOIN guests g ON r.guest_id = g.id
            JOIN rooms rm ON r.room_id = rm.id
            JOIN room_types rt ON rm.room_type_id = rt.id
            ORDER BY r.check_in DESC
        ");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Add new reservation
    public function addReservation($fullName, $email, $phone, $roomTypeId, $checkInDate, $checkOutDate, $numGuests) {
        // Sanitize inputs
        $fullName = sanitizeInput($fullName);
        $email = sanitizeInput($email);
        $phone = sanitizeInput($phone);
        
        // Validate phone number (11 digits)
        if (!preg_match('/^\d{11}$/', $phone)) {
            return ['success' => false, 'message' => 'Phone number must be 11 digits'];
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Find available room of the selected type
        $stmt = $this->conn->prepare("
            SELECT r.id FROM rooms r 
            WHERE r.room_type_id = ? AND r.status = 'available'
            LIMIT 1
        ");
        $stmt->bind_param("i", $roomTypeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'No available rooms of this type'];
        }
        
        $room = $result->fetch_assoc();
        $roomId = $room['id'];
        
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Create or update guest
            $stmt = $this->conn->prepare("INSERT INTO guests (full_name, email, phone) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $fullName, $email, $phone);
            $stmt->execute();
            $guestId = $this->conn->insert_id;
            
            // Calculate total price (example implementation - adjust based on your pricing logic)
            $stmt = $this->conn->prepare("
                SELECT price_per_night FROM room_types WHERE id = ?
            ");
            $stmt->bind_param("i", $roomTypeId);
            $stmt->execute();
            $priceResult = $stmt->get_result()->fetch_assoc();
            $pricePerNight = $priceResult['price_per_night'];
            
            // Calculate number of nights
            $checkIn = new DateTime($checkInDate);
            $checkOut = new DateTime($checkOutDate);
            $numNights = $checkIn->diff($checkOut)->days;
            $totalPrice = $pricePerNight * $numNights;
            
            // Get current admin ID if logged in
            $adminId = $this->auth->isLoggedIn() ? $this->auth->getCurrentUserId() : null;
            
            // Create reservation
            $stmt = $this->conn->prepare("
                INSERT INTO Reservation (
                    guest_id, room_id, admin_id, check_in, check_out,
                    total_price, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'reserved')
            ");
            
            $stmt->bind_param("iiissd", $guestId, $roomId, $adminId, $checkInDate, $checkOutDate, $totalPrice);
            $stmt->execute();
            $reservationId = $this->conn->insert_id;
            
            // Update room status
            $stmt = $this->conn->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
            $stmt->bind_param("i", $roomId);
            $stmt->execute();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Reservation created successfully',
                'reservation_id' => $reservationId
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error creating reservation: ' . $e->getMessage()];
        }
    }
    
    // Update reservation status
    public function updateReservationStatus($reservationId, $status) {
        if (!$this->auth->isLoggedIn()) {
            return ['success' => false, 'message' => 'Unauthorized access'];
        }
        
        $validStatuses = ['reserved', 'checked-in', 'checked-out', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        $this->conn->begin_transaction();
        
        try {
            // Get room ID for this reservation
            $stmt = $this->conn->prepare("SELECT room_id FROM Reservation WHERE reservation_id = ?");
            $stmt->bind_param("i", $reservationId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Reservation not found");
            }
            
            $reservation = $result->fetch_assoc();
            $roomId = $reservation['room_id'];
            
            // Update reservation status
            $stmt = $this->conn->prepare("UPDATE Reservation SET status = ? WHERE reservation_id = ?");
            $stmt->bind_param("si", $status, $reservationId);
            $stmt->execute();
            
            // Update room status based on reservation status
            $roomStatus = $status === 'checked-out' || $status === 'cancelled' ? 'available' : 'occupied';
            $stmt = $this->conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $roomStatus, $roomId);
            $stmt->execute();
            
            $this->conn->commit();
            return ['success' => true, 'message' => 'Reservation status updated successfully'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Failed to update reservation status: ' . $e->getMessage()];
        }
    }
    
    public function __destruct() {
        $this->conn->close();
    }
}
?>