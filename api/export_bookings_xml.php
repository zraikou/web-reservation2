<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="bookings_export.xml"');

require_once '../admin/config/db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die('<error>Database connection failed</error>');
}

$sql = "SELECT r.*, g.guest_id, g.first_name, g.last_name, g.email, g.phone, g.address, g.created_at, rm.room_id, rm.room_type_id, rm.room_number, rm.floor, rm.status AS room_status, rm.notes, rm.price_per_night, rm.capacity FROM Reservation r JOIN Guest g ON r.guest_id = g.guest_id JOIN Room rm ON r.room_id = rm.room_id";
$result = $conn->query($sql);

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<bookings>\n";
while ($row = $result->fetch_assoc()) {
    echo "  <booking>\n";
    echo "    <reservation_id>{$row['reservation_id']}</reservation_id>\n";
    echo "    <check_in>{$row['check_in']}</check_in>\n";
    echo "    <check_out>{$row['check_out']}</check_out>\n";
    echo "    <total_price>{$row['total_price']}</total_price>\n";
    echo "    <status>{$row['status']}</status>\n";
    echo "    <guest>\n";
    echo "      <guest_id>{$row['guest_id']}</guest_id>\n";
    echo "      <first_name>" . htmlspecialchars($row['first_name']) . "</first_name>\n";
    echo "      <last_name>" . htmlspecialchars($row['last_name']) . "</last_name>\n";
    echo "      <email>" . htmlspecialchars($row['email']) . "</email>\n";
    echo "      <phone>" . htmlspecialchars($row['phone']) . "</phone>\n";
    echo "      <address>" . htmlspecialchars($row['address']) . "</address>\n";
    echo "      <created_at>{$row['created_at']}</created_at>\n";
    echo "    </guest>\n";
    echo "    <room>\n";
    echo "      <room_id>{$row['room_id']}</room_id>\n";
    echo "      <room_type_id>{$row['room_type_id']}</room_type_id>\n";
    echo "      <room_number>" . htmlspecialchars($row['room_number']) . "</room_number>\n";
    echo "      <floor>{$row['floor']}</floor>\n";
    echo "      <status>" . htmlspecialchars($row['room_status']) . "</status>\n";
    echo "      <notes>" . htmlspecialchars($row['notes']) . "</notes>\n";
    echo "      <price_per_night>{$row['price_per_night']}</price_per_night>\n";
    echo "      <capacity>{$row['capacity']}</capacity>\n";
    echo "    </room>\n";
    echo "  </booking>\n";
}
echo "</bookings>\n";
$conn->close(); 