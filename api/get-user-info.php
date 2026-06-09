<?php
require_once __DIR__ . '/../config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Not authenticated');
}

$userId = $_SESSION['user_id'];
$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare('SELECT user_id, full_name, email, phone, role FROM users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(false, 'User not found');
    }

    jsonResponse(true, 'User info retrieved', [
        'user' => [
            'id' => (int) $user['user_id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'],
        ]
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    jsonResponse(false, 'Database error');
}
?>