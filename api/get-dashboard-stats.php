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
    $stats = [];

    if ($role === 'patient') {
        // Patient stats
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM appointments WHERE patient_id = ?');
        $stmt->execute([$userId]);
        $stats['totalAppointments'] = (int) $stmt->fetch()['count'];

        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND status = "pending"');
        $stmt->execute([$userId]);
        $stats['pendingAppointments'] = (int) $stmt->fetch()['count'];

        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND status = "completed"');
        $stmt->execute([$userId]);
        $stats['completedAppointments'] = (int) $stmt->fetch()['count'];

        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM doctors');
        $stats['availableDoctors'] = (int) $stmt->fetch()['count'];
    } elseif ($role === 'doctor') {
        // Doctor stats
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id WHERE d.user_id = ?');
        $stmt->execute([$userId]);
        $stats['totalAppointments'] = (int) $stmt->fetch()['count'];

        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id WHERE d.user_id = ? AND a.status = "pending"');
        $stmt->execute([$userId]);
        $stats['pendingAppointments'] = (int) $stmt->fetch()['count'];

        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id WHERE d.user_id = ? AND a.status = "completed"');
        $stmt->execute([$userId]);
        $stats['completedAppointments'] = (int) $stmt->fetch()['count'];

        $stmt = $pdo->prepare('SELECT COUNT(DISTINCT patient_id) as count FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id WHERE d.user_id = ?');
        $stmt->execute([$userId]);
        $stats['totalPatients'] = (int) $stmt->fetch()['count'];
    } elseif ($role === 'admin') {
        // Admin stats
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
        $stats['totalUsers'] = (int) $stmt->fetch()['count'];

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM appointments');
        $stats['totalAppointments'] = (int) $stmt->fetch()['count'];

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM doctors');
        $stats['totalDoctors'] = (int) $stmt->fetch()['count'];

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM clinics');
        $stats['totalClinics'] = (int) $stmt->fetch()['count'];
    }

    jsonResponse(true, 'Stats retrieved', ['stats' => $stats]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    jsonResponse(false, 'Database error');
}
?>