<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

$dbHost = 'localhost';
$dbName = 'DoctorPatient';
$dbUser = 'root';
$dbPass = '';

function json_response(bool $success, string $message, array $extra = []): void
{
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.');
}

$fullName = trim($_POST['full_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$phone = trim($_POST['phone'] ?? '');
$role = $_POST['role'] ?? 'patient';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$allowedRoles = ['patient', 'doctor', 'admin'];

if (strlen($fullName) < 2) {
    json_response(false, 'Please enter your full name.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Please enter a valid email address.');
}

if (!in_array($role, $allowedRoles, true)) {
    json_response(false, 'Please choose a valid account type.');
}

if (strlen($password) < 6) {
    json_response(false, 'Password must be at least 6 characters.');
}

if ($password !== $confirmPassword) {
    json_response(false, 'Passwords do not match.');
}

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $checkStmt->execute([$email]);

    if ($checkStmt->fetch()) {
        json_response(false, 'An account with this email already exists.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $insertStmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)'
    );
    $insertStmt->execute([$fullName, $email, $passwordHash, $phone !== '' ? $phone : null, $role]);

    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;

    json_response(true, 'Account created successfully.', [
        'user' => [
            'id' => $_SESSION['user_id'],
            'name' => $fullName,
            'email' => $email,
            'role' => $role,
        ],
    ]);
} catch (PDOException $exception) {
    json_response(false, 'Database connection failed. Check your database settings.');
}