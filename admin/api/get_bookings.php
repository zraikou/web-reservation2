<?php
// get_bookings.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

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

// Sanitize inputs
$page     = max(1, (int)($_GET['page']  ?? 1));
$limit    = max(1, (int)($_GET['limit'] ?? 10));
$offset   = ($page - 1) * $limit;
$dateFrom = $conn->real_escape_string($_GET['date_from'] ?? '');
$dateTo   = $conn->real_escape_string($_GET['date_to']   ?? '');
$status   = $conn->real_escape_string($_GET['status']    ?? '');
$roomType = (int)($_GET['room_type'] ?? 0);
$search   = $conn->real_escape_string($_GET['search']    ?? '');

// Build WHERE clauses
$where = ["1=1"];
if ($dateFrom)  $where[] = "r.check_in  >= '$dateFrom'";
if ($dateTo)    $where[] = "r.check_out <= '$dateTo'";
if ($status)    $where[] = "r.status    = '$status'";
if ($roomType)  $where[] = "r.room_id   = $roomType";
if ($search) {
    $where[] = "(CONCAT(g.first_name, ' ', g.last_name) LIKE '%$search%' OR g.phone LIKE '%$search%' OR g.email LIKE '%$search%')";
}
$whereSql = implode(' AND ', $where);

// Total count for pagination
$cntSql = "SELECT COUNT(*) AS cnt FROM Reservation r JOIN Guest g ON r.guest_id = g.guest_id JOIN Room rm ON r.room_id = rm.room_id WHERE $whereSql";
if (! $cntRes = $conn->query($cntSql)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Count error: '.$conn->error
    ]);
    exit;
}
$total = (int)$cntRes->fetch_assoc()['cnt'];

// Fetch page of reservations
$sql = "
  SELECT
    r.reservation_id AS id,
    CONCAT(g.first_name, ' ', g.last_name) AS guest_name,
    g.phone AS contact,
    rt.name AS room,
    r.check_in,
    r.check_out,
    rm.capacity AS guests,
    r.status
  FROM Reservation r
  JOIN Guest g ON r.guest_id = g.guest_id
  JOIN Room rm ON r.room_id = rm.room_id
  JOIN room_types rt ON rm.room_type_id = rt.id
  WHERE $whereSql
  ORDER BY r.check_in DESC
  LIMIT $limit
  OFFSET $offset
";

if (! $res = $conn->query($sql)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Fetch error: '.$conn->error
    ]);
    exit;
}

// Build response array
$reservations = [];
while ($row = $res->fetch_assoc()) {
    $reservations[] = [
        'id'         => (int)$row['id'],
        'guest_name' => $row['guest_name'],
        'contact'    => $row['contact'],
        'room'       => $row['room'],
        'check_in'   => $row['check_in'],
        'check_out'  => $row['check_out'],
        'guests'     => (int)$row['guests'],
        'status'     => $row['status']
    ];
}

// Return JSON with corrected response format
echo json_encode([
    'status'      => 'success',
    'bookings'    => $reservations,
    'page'        => $page,
    'total_pages' => (int)ceil($total / $limit),
    'total'       => $total
]);

$conn->close();
?>