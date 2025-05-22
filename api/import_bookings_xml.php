<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../admin/config/db_config.php';

if (!isset($_FILES['xml_file']) || $_FILES['xml_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$xmlContent = file_get_contents($_FILES['xml_file']['tmp_name']);
libxml_use_internal_errors(true);
$xml = simplexml_load_string($xmlContent);
if ($xml === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid XML file.']);
    exit;
}

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$imported = 0;
foreach ($xml->booking as $booking) {
    // Guest upsert by email
    $guest = $booking->guest;
    $email = $conn->real_escape_string((string)$guest->email);
    $guest_id = null;
    $guestCheck = $conn->query("SELECT guest_id FROM Guest WHERE email='$email'");
    if ($guestCheck && $guestCheck->num_rows > 0) {
        $row = $guestCheck->fetch_assoc();
        $guest_id = $row['guest_id'];
        $updateGuest = $conn->query("UPDATE Guest SET first_name='" . $conn->real_escape_string((string)$guest->first_name) . "', last_name='" . $conn->real_escape_string((string)$guest->last_name) . "', phone='" . $conn->real_escape_string((string)$guest->phone) . "', address='" . $conn->real_escape_string((string)$guest->address) . "' WHERE guest_id=$guest_id");
        if (!$updateGuest) {
            echo json_encode(['success' => false, 'message' => 'Guest update failed: ' . $conn->error]);
            $conn->close();
            exit;
        }
    } else {
        $insertGuest = $conn->query("INSERT INTO Guest (first_name, last_name, email, phone, address, created_at) VALUES ('" . $conn->real_escape_string((string)$guest->first_name) . "', '" . $conn->real_escape_string((string)$guest->last_name) . "', '$email', '" . $conn->real_escape_string((string)$guest->phone) . "', '" . $conn->real_escape_string((string)$guest->address) . "', NOW())");
        if (!$insertGuest) {
            echo json_encode(['success' => false, 'message' => 'Guest insert failed: ' . $conn->error]);
            $conn->close();
            exit;
        }
        $guest_id = $conn->insert_id;
    }

    // Room upsert by room_number
    $room = $booking->room;
    $room_number = $conn->real_escape_string((string)$room->room_number);
    $room_id = null;
    $roomCheck = $conn->query("SELECT room_id FROM Room WHERE room_number='$room_number'");
    if ($roomCheck && $roomCheck->num_rows > 0) {
        $row = $roomCheck->fetch_assoc();
        $room_id = $row['room_id'];
        $updateRoom = $conn->query("UPDATE Room SET room_type_id='" . $conn->real_escape_string((string)$room->room_type_id) . "', floor='" . $conn->real_escape_string((string)$room->floor) . "', status='" . $conn->real_escape_string((string)$room->status) . "', notes='" . $conn->real_escape_string((string)$room->notes) . "', price_per_night='" . $conn->real_escape_string((string)$room->price_per_night) . "', capacity='" . $conn->real_escape_string((string)$room->capacity) . "' WHERE room_id=$room_id");
        if (!$updateRoom) {
            echo json_encode(['success' => false, 'message' => 'Room update failed: ' . $conn->error]);
            $conn->close();
            exit;
        }
    } else {
        $insertRoom = $conn->query("INSERT INTO Room (room_type_id, room_number, floor, status, notes, price_per_night, capacity) VALUES ('" . $conn->real_escape_string((string)$room->room_type_id) . "', '$room_number', '" . $conn->real_escape_string((string)$room->floor) . "', '" . $conn->real_escape_string((string)$room->status) . "', '" . $conn->real_escape_string((string)$room->notes) . "', '" . $conn->real_escape_string((string)$room->price_per_night) . "', '" . $conn->real_escape_string((string)$room->capacity) . "')");
        if (!$insertRoom) {
            echo json_encode(['success' => false, 'message' => 'Room insert failed: ' . $conn->error]);
            $conn->close();
            exit;
        }
        $room_id = $conn->insert_id;
    }

    // Reservation upsert by reservation_id
    $reservation_id = (int)$booking->reservation_id;
    // Ignore admin_id completely
    $check_in = $conn->real_escape_string((string)$booking->check_in);
    $check_out = $conn->real_escape_string((string)$booking->check_out);
    $total_price = $conn->real_escape_string((string)$booking->total_price);
    $status = $conn->real_escape_string((string)$booking->status);
    $resCheck = $conn->query("SELECT reservation_id FROM Reservation WHERE reservation_id=$reservation_id");
    if ($resCheck && $resCheck->num_rows > 0) {
        $updateRes = $conn->query("UPDATE Reservation SET guest_id=$guest_id, room_id=$room_id, check_in='$check_in', check_out='$check_out', total_price='$total_price', status='$status' WHERE reservation_id=$reservation_id");
        if (!$updateRes) {
            echo json_encode(['success' => false, 'message' => 'Reservation update failed: ' . $conn->error]);
            $conn->close();
            exit;
        }
    } else {
        $insertRes = $conn->query("INSERT INTO Reservation (reservation_id, guest_id, room_id, check_in, check_out, total_price, status) VALUES ($reservation_id, $guest_id, $room_id, '$check_in', '$check_out', '$total_price', '$status')");
        if (!$insertRes) {
            echo json_encode(['success' => false, 'message' => 'Reservation insert failed: ' . $conn->error]);
            $conn->close();
            exit;
        }
    }
    $imported++;
}
$conn->close();
echo json_encode(['success' => true, 'message' => "Imported $imported bookings."]); 