<?php
declare(strict_types=1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'DoctorPatient');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'Doctor-Patient Appointment System');
define('APP_URL', 'http://localhost/Doctor-Patients-Appointment-system');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Create PDO Connection Function
function getDBConnection(): PDO
{
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

// Helper function for JSON responses
function jsonResponse(bool $success, string $message, array $extra = []): void
{
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
    ], $extra));
    exit;
}

// Authentication check
function requireAuth(string $requiredRole = null): void
{
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'Unauthorized access. Please login first.');
    }

    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        jsonResponse(false, 'Insufficient permissions for this action.');
    }
}

// Sanitize input
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>