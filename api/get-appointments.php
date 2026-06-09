<?php
require_once __DIR__ . '/../config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Not authenticated');
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$pdo = getDBConnection();

try {
    $appointments = [];

    if ($role === 'patient') {
        $stmt = $pdo->prepare('
            SELECT a.*, u.full_name as doctor_name, c.name as clinic_name
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.user_id
            LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ');
        $stmt->execute([$userId]);
        $appointments = $stmt->fetchAll();
    } elseif ($role === 'doctor') {
        $stmt = $pdo->prepare('
            SELECT a.*, u.full_name as patient_name, c.name as clinic_name
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users u ON a.patient_id = u.user_id
            LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
            WHERE d.user_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ');
        $stmt->execute([$userId]);
        $appointments = $stmt->fetchAll();
    } elseif ($role === 'admin') {
        $stmt = $pdo->query('
            SELECT a.*, u.full_name as patient_name, d2.full_name as doctor_name, c.name as clinic_name
            FROM appointments a
            JOIN users u ON a.patient_id = u.user_id
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users d2 ON d.user_id = d2.user_id
            LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ');
        $appointments = $stmt->fetchAll();
    }

    jsonResponse(true, 'Appointments retrieved', ['appointments' => $appointments]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    jsonResponse(false, 'Database error');
}
?>