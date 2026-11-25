<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$username = strtolower(trim((string) getenv('ADMIN_USERNAME')));
$email = filter_var((string) getenv('ADMIN_EMAIL'), FILTER_VALIDATE_EMAIL);
$password = getenv('ADMIN_PASSWORD');

if (
    !preg_match('/^[a-z0-9][a-z0-9_-]{2,49}$/', $username) ||
    !$email ||
    $password === false ||
    strlen($password) < 12 ||
    strlen($password) > 72
) {
    fwrite(STDERR, "Set ADMIN_USERNAME, ADMIN_EMAIL, and an ADMIN_PASSWORD of 12 to 72 bytes before running this CLI script.\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host=localhost;dbname=T@ke9ine;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([$username, $email]);
if ($stmt->fetchColumn() !== false) {
    fwrite(STDERR, "That username or email already exists. No account was changed.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $pdo->prepare(
    "INSERT INTO admins (username, email, password_hash, role)
     VALUES (?, ?, ?, 'admin')"
);
$stmt->execute([$username, $email, $passwordHash]);

fwrite(STDOUT, "Admin account created for {$email}.\n");
