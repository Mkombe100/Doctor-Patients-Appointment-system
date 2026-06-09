<?php
require_once __DIR__ . '/../config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Not authenticated');
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->query('
        SELECT d.doctor_id, u.full_name, d.specialization, d.experience_years, d.license_number, d.bio
        FROM doctors d
        JOIN users u ON d.user_id = u.user_id
        ORDER BY u.full_name
    ');
    $doctors = $stmt->fetchAll();

    jsonResponse(true, 'Doctors retrieved', ['doctors' => $doctors]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    jsonResponse(false, 'Database error');
}
?>