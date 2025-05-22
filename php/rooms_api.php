<?php
require_once 'room_functions.php';

header('Content-Type: application/json');

$manager = new RoomManager();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_all_rooms':
            echo json_encode($manager->getAllRooms());
            break;
        case 'get_room_types':
            echo json_encode($manager->getRoomTypes());
            break;
        case 'get_room':
            $roomId = intval($_GET['id'] ?? 0);
            echo json_encode($manager->getRoomById($roomId));
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid GET action']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_room':
            $roomTypeId = intval($_POST['room_type_id'] ?? 0);
            $roomNumber = $_POST['room_number'] ?? '';
            $floor = $_POST['floor'] ?? '';
            $status = $_POST['status'] ?? 'available';
            echo json_encode($manager->addRoom($roomTypeId, $roomNumber, $floor, $status));
            break;

        case 'update_room':
            $roomId = intval($_POST['id'] ?? 0);
            $roomTypeId = intval($_POST['room_type_id'] ?? 0);
            $roomNumber = $_POST['room_number'] ?? '';
            $floor = $_POST['floor'] ?? '';
            $status = $_POST['status'] ?? 'available';
            echo json_encode($manager->updateRoom($roomId, $roomTypeId, $roomNumber, $floor, $status));
            break;

        case 'delete_room':
            $roomId = intval($_POST['id'] ?? 0);
            echo json_encode($manager->deleteRoom($roomId));
            break;

        case 'add_room_type':
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = floatval($_POST['price_per_night'] ?? 0);
            $capacity = intval($_POST['capacity'] ?? 0);
            echo json_encode($manager->addRoomType($name, $description, $price, $capacity));
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid POST action']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unsupported request']);
