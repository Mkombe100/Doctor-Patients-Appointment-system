<?php
require_once __DIR__ . '/../config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Not authenticated');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$userId = $_SESSION['user_id'];
$fullName = sanitize($_POST['full_name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');

if (strlen($fullName) < 2) {
    jsonResponse(false, 'Full name must be at least 2 characters');
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?');
    $stmt->execute([$fullName, $phone, $userId]);

    $_SESSION['full_name'] = $fullName;

    jsonResponse(true, 'Profile updated successfully');
} catch (PDOException $e) {
    error_log($e->getMessage());
    jsonResponse(false, 'Database error');
}
?>