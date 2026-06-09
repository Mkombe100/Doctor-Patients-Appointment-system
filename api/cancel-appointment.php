<?php
require_once __DIR__ . '/../config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Not authenticated');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$appointmentId = (int) ($_POST['appointment_id'] ?? 0);
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (!$appointmentId) {
    jsonResponse(false, 'Invalid appointment ID');
}

$pdo = getDBConnection();

try {
    // Get appointment details
    $stmt = $pdo->prepare('SELECT patient_id, doctor_id, status FROM appointments WHERE appointment_id = ?');
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        jsonResponse(false, 'Appointment not found');
    }

    // Check authorization
    if ($role === 'patient' && $appointment['patient_id'] != $userId) {
        jsonResponse(false, 'Not authorized to cancel this appointment');
    }

    if ($role === 'doctor') {
        $stmt = $pdo->prepare('SELECT user_id FROM doctors WHERE doctor_id = ?');
        $stmt->execute([$appointment['doctor_id']]);
        $doctor = $stmt->fetch();
        if ($doctor['user_id'] != $userId) {
            jsonResponse(false, 'Not authorized');
        }
    }

    if ($appointment['status'] === 'cancelled' || $appointment['status'] === 'completed') {
        jsonResponse(false, 'Cannot cancel this appointment');
    }

    // Update appointment status
    $stmt = $pdo->prepare('UPDATE appointments SET status = "cancelled" WHERE appointment_id = ?');
    $stmt->execute([$appointmentId]);

    jsonResponse(true, 'Appointment cancelled successfully');
} catch (PDOException $e) {
    error_log($e->getMessage());
    jsonResponse(false, 'Database error');
}
?>