<?php

require_once __DIR__ . '/session.php';

if (!isHttpsRequest()) {
    http_response_code(403);
    exit('HTTPS is required to access the admin area.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

startAdminSession();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

session_destroy();
header('Location: admin_login.php');
exit;
