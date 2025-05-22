<?php
require_once 'config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = [
    'isLoggedIn' => isset($_SESSION['guest_id']),
    'userData' => null
];

if ($response['isLoggedIn']) {
    $response['userData'] = [
        'first_name' => $_SESSION['first_name'] ?? '',
        'username' => $_SESSION['username'] ?? ''
    ];
}

echo json_encode($response); 