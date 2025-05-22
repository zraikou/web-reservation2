<?php
require_once 'config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = [
    'isAdmin' => false,
    'adminData' => null
];

// Check if admin is logged in
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_role'])) {
    $response['isAdmin'] = true;
    $response['adminData'] = [
        'username' => $_SESSION['admin_username'] ?? '',
        'role' => $_SESSION['admin_role'] ?? ''
    ];
}

echo json_encode($response); 