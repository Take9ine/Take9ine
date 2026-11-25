<?php

require_once __DIR__ . '/admin_session.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $statusCode, array $response): void
{
    http_response_code($statusCode);
    echo json_encode($response);
    exit;
}

if (!isHttpsRequest()) {
    respond(403, ['status' => 'error', 'message' => 'HTTPS is required to sign in.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['status' => 'error', 'message' => 'Method not allowed.']);
}

$username = strtolower(trim((string) ($_POST['username'] ?? '')));
$password = $_POST['password'] ?? '';

if (
    !preg_match('/^[a-z0-9][a-z0-9_-]{2,49}$/', $username) ||
    !is_string($password) ||
    $password === ''
) {
    respond(400, ['status' => 'error', 'message' => 'Username and password are required.']);
}

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=T@ke9ine;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $stmt = $pdo->prepare(
        "SELECT id, username, email, password_hash, role
         FROM admins
         WHERE username = ?
         LIMIT 1"
    );
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
} catch (PDOException $exception) {
    error_log('Admin login database error: ' . $exception->getMessage());
    respond(500, ['status' => 'error', 'message' => 'Sign-in is temporarily unavailable.']);
}

if (
    !$admin ||
    $admin['role'] !== 'admin' ||
    !password_verify($password, $admin['password_hash'])
) {
    respond(401, ['status' => 'error', 'message' => 'Invalid username or password.']);
}

startAdminSession();
session_regenerate_id(true);
$_SESSION['admin_id'] = (int) $admin['id'];
$_SESSION['admin_email'] = $admin['email'];
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['admin_role'] = $admin['role'];

respond(200, [
    'status' => 'success',
    'redirect' => 'admin_dashboard.php',
]);
