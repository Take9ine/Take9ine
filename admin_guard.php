<?php

require_once __DIR__ . '/admin_session.php';

if (!isHttpsRequest()) {
    http_response_code(403);
    exit('HTTPS is required to access the admin area.');
}

startAdminSession();

if (
    empty($_SESSION['admin_id']) ||
    ($_SESSION['admin_role'] ?? null) !== 'admin'
) {
    header('Location: admin_login.php');
    exit;
}
