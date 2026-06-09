<?php
require_once __DIR__ . '/../config.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    jsonResponse(false, 'Not authorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$patientId = $_SESSION['user_id'];
$doctorId = (int) ($_POST['doctor_id'] ?? 0);
$appointmentDate = sanitize($_POST['appointment_date'] ?? '');
$appointmentTime = sanitize($_POST['appointment_time'] ?? '');
$reason = sanitize($_POST['reason'] ?? '');

if (!$doctorId || !$appointmentDate || !$appointmentTime) {
    jsonResponse(false, 'Missing required fields');
}

// Validate date and time format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate)) {
    jsonResponse(false, 'Invalid date format');
}

if (!preg_match('/^\d{2}:\d{2}$/', $appointmentTime)) {
    jsonResponse(false, 'Invalid time format');
}

$pdo = getDBConnection();

try {
    // Check if doctor exists
    $stmt = $pdo->prepare('SELECT doctor_id FROM doctors WHERE doctor_id = ?');
    $stmt->execute([$doctorId]);
    if (!$stmt->fetch()) {
        jsonResponse(false, 'Doctor not found');
    }

    // Check for conflicting appointments
    $stmt = $pdo->prepare('
        SELECT appointment_id FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
        AND status != "cancelled"
        LIMIT 1
    ');
    $stmt->execute([$doctorId, $appointmentDate, $appointmentTime]);
    if ($stmt->fetch()) {
        jsonResponse(false, 'Time slot is already booked');
    }

    // Insert appointment
    $stmt = $pdo->prepare('
        INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status)
        VALUES (?, ?, ?, ?, ?, "pending")
    ');
    $stmt->execute([$patientId, $doctorId, $appointmentDate, $appointmentTime, $reason]);

    jsonResponse(true, 'Appointment booked successfully', [
        'appointment_id' => (int) $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    jsonResponse(false, 'Database error');
}
?>