<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

function authPostString(string $key): ?string
{
    $value = $_POST[$key] ?? null;
    return is_string($value) ? $value : null;
}

function startAuthSession(): void
{
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $forwardedProto = strtolower(trim(explode(
        ',',
        (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')
    )[0]));
    $isHttps = (
        isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== '' &&
        strtolower((string) $_SERVER['HTTPS']) !== 'off'
    ) || $forwardedProto === 'https';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function respondAuthJson(int $statusCode, array $response): void
{
    http_response_code($statusCode);
    echo json_encode($response);
    exit;
}

$action = authPostString('action') ?? '';
if (!in_array($action, ['request_password_reset', 'reset_password', 'register', 'login'], true)) {
    respondAuthJson(400, ['status' => 'error', 'message' => 'Unknown action.']);
}

try {
    $pdo = appDatabase();
} catch (PDOException $e) {
    error_log('Authentication database connection failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Authentication is temporarily unavailable.']);
    exit;
} catch (RuntimeException $e) {
    error_log('Authentication configuration failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Authentication is temporarily unavailable.']);
    exit;
}

// --- ACTION: REQUEST PASSWORD RESET ---
if ($action === 'request_password_reset') {
    $emailInput = $_POST['email'] ?? '';
    $email = is_string($emailInput)
        ? filter_var(trim($emailInput), FILTER_VALIDATE_EMAIL)
        : false;
    if (!$email) {
        respondAuthJson(400, [
            'status' => 'error',
            'message' => 'Enter a valid email address.'
        ]);
    }

    if (
        trim((string) getenv('GMAIL_CLIENT_ID')) === '' ||
        trim((string) getenv('GMAIL_CLIENT_SECRET')) === '' ||
        trim((string) getenv('GMAIL_REFRESH_TOKEN')) === '' ||
        !function_exists('curl_init')
    ) {
        error_log('Password reset email delivery is not configured.');
        respondAuthJson(503, [
            'status' => 'error',
            'message' => 'Password reset is temporarily unavailable. Please try again later.'
        ]);
    }

    $genericResetMessage =
        'If an account matches that email, a reset code may be sent. ' .
        'Check spam; if no code arrives within a minute, request another code.';

    try {
        $userStmt = $pdo->prepare(
            'SELECT id FROM users WHERE email = ? LIMIT 1'
        );
        $userStmt->execute([$email]);
        $userId = $userStmt->fetchColumn();

        if ($userId === false) {
            respondAuthJson(202, [
                'status' => 'accepted',
                'message' => $genericResetMessage,
                'challenge_id' => bin2hex(random_bytes(32))
            ]);
        }

        $userId = (int) $userId;
        $pdo->beginTransaction();
        $lockUserStmt = $pdo->prepare(
            'SELECT id FROM users WHERE id = ? FOR UPDATE'
        );
        $lockUserStmt->execute([$userId]);

        if ($lockUserStmt->fetchColumn() === false) {
            $pdo->commit();
            respondAuthJson(202, [
                'status' => 'accepted',
                'message' => $genericResetMessage,
                'challenge_id' => bin2hex(random_bytes(32))
            ]);
        }

        $recentChallengeStmt = $pdo->prepare(
            'SELECT challenge_id
             FROM password_reset_tokens
             WHERE user_id = ?
               AND created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 MINUTE)
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $recentChallengeStmt->execute([$userId]);
        $recentChallengeId = $recentChallengeStmt->fetchColumn();

        if (is_string($recentChallengeId)) {
            $pdo->commit();
            respondAuthJson(202, [
                'status' => 'accepted',
                'message' => $genericResetMessage,
                'challenge_id' => $recentChallengeId
            ]);
        }

        $cleanupStmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE expires_at <= ?');
        $cleanupStmt->execute([time()]);

        $challengeId = bin2hex(random_bytes(32));
        $resetCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = time() + 600;

        $deletePreviousStmt = $pdo->prepare(
            'DELETE FROM password_reset_tokens WHERE user_id = ?'
        );
        $deletePreviousStmt->execute([$userId]);

        $insertStmt = $pdo->prepare(
            'INSERT INTO password_reset_tokens (
                challenge_id,
                user_id,
                code_hash,
                expires_at,
                failed_attempts
            ) VALUES (?, ?, ?, ?, 0)'
        );
        $insertStmt->execute([
            $challengeId,
            $userId,
            password_hash($resetCode, PASSWORD_DEFAULT),
            $expiresAt
        ]);
        $pdo->commit();

        require_once __DIR__ . '/gmail_api.php';
        $textBody =
            "Your T@ke9ine password reset code is {$resetCode}.\n\n" .
            "This code expires in 10 minutes. If you did not request a password " .
            "reset, you can ignore this message.";
        $htmlBody =
            '<p>Your T@ke9ine password reset code is:</p>' .
            '<p style="font-size:24px;font-weight:bold;letter-spacing:4px">' .
            $resetCode .
            '</p><p>This code expires in 10 minutes. If you did not request a ' .
            'password reset, you can ignore this message.</p>';

        if (!sendGmailApiMessage(
            $email,
            'T@ke9ine password reset code',
            $htmlBody,
            $textBody
        )) {
            error_log('Password reset code delivery failed.');
        }

        respondAuthJson(202, [
            'status' => 'accepted',
            'message' => $genericResetMessage,
            'challenge_id' => $challengeId
        ]);
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Password reset request database error: ' . $exception->getMessage());
        respondAuthJson(500, [
            'status' => 'error',
            'message' => 'Password reset is temporarily unavailable.'
        ]);
    }
}

// --- ACTION: RESET PASSWORD ---
if ($action === 'reset_password') {
    $emailInput = $_POST['email'] ?? '';
    $email = is_string($emailInput)
        ? filter_var(trim($emailInput), FILTER_VALIDATE_EMAIL)
        : false;
    $challengeId = $_POST['challenge_id'] ?? '';
    $resetCode = $_POST['reset_code'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['confirm_password'] ?? '';

    if (
        !$email ||
        !is_string($challengeId) ||
        strlen($challengeId) !== 64 ||
        !preg_match('/^[a-f0-9]{64}$/', $challengeId) ||
        !is_string($resetCode) ||
        !is_string($password) ||
        !is_string($passwordConfirmation) ||
        strlen($password) < 6 ||
        strlen($password) > 72 ||
        strpos($password, "\0") !== false ||
        !hash_equals($password, $passwordConfirmation)
    ) {
        respondAuthJson(400, [
            'status' => 'error',
            'message' => 'Enter a valid reset code and matching password of 6 to 72 characters.'
        ]);
    }

    try {
        $userStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $userStmt->execute([$email]);
        $userId = $userStmt->fetchColumn();

        if ($userId === false) {
            respondAuthJson(400, [
                'status' => 'error',
                'message' => 'The reset code is invalid or expired.'
            ]);
        }

        $userId = (int) $userId;
        $pdo->beginTransaction();

        $lockClause = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? ' FOR UPDATE'
            : '';
        $challengeStmt = $pdo->prepare(
            'SELECT code_hash, expires_at, failed_attempts
             FROM password_reset_tokens
             WHERE challenge_id = ? AND user_id = ?' . $lockClause
        );
        $challengeStmt->execute([$challengeId, $userId]);
        $challenge = $challengeStmt->fetch();

        if (!$challenge) {
            $pdo->commit();
            respondAuthJson(400, [
                'status' => 'error',
                'message' => 'The reset code is invalid or expired.'
            ]);
        }

        if ((int) $challenge['expires_at'] <= time()) {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM password_reset_tokens WHERE challenge_id = ?'
            );
            $deleteStmt->execute([$challengeId]);
            $pdo->commit();
            respondAuthJson(400, [
                'status' => 'error',
                'message' => 'The reset code is invalid or expired.'
            ]);
        }

        if ((int) $challenge['failed_attempts'] >= 5) {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM password_reset_tokens WHERE challenge_id = ?'
            );
            $deleteStmt->execute([$challengeId]);
            $pdo->commit();
            respondAuthJson(400, [
                'status' => 'error',
                'message' => 'The reset code is invalid or expired. Request a new code.'
            ]);
        }

        if (
            strlen($resetCode) !== 6 ||
            !preg_match('/^\d{6}$/', $resetCode) ||
            !password_verify($resetCode, (string) $challenge['code_hash'])
        ) {
            $attempts = (int) $challenge['failed_attempts'] + 1;
            if ($attempts >= 5) {
                $deleteStmt = $pdo->prepare(
                    'DELETE FROM password_reset_tokens WHERE challenge_id = ?'
                );
                $deleteStmt->execute([$challengeId]);
            } else {
                $updateAttemptsStmt = $pdo->prepare(
                    'UPDATE password_reset_tokens
                     SET failed_attempts = ?
                     WHERE challenge_id = ?'
                );
                $updateAttemptsStmt->execute([$attempts, $challengeId]);
            }

            $pdo->commit();
            respondAuthJson(400, [
                'status' => 'error',
                'message' => 'The reset code is invalid or expired.'
            ]);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $updatePasswordStmt = $pdo->prepare(
            'UPDATE users
             SET password_hash = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?'
        );
        $updatePasswordStmt->execute([$passwordHash, $userId]);

        $deleteUserChallengesStmt = $pdo->prepare(
            'DELETE FROM password_reset_tokens WHERE user_id = ?'
        );
        $deleteUserChallengesStmt->execute([$userId]);

        $pdo->commit();
        respondAuthJson(200, [
            'status' => 'success',
            'message' => 'Your password has been reset. You can now sign in.'
        ]);
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Password reset database error: ' . $exception->getMessage());
        respondAuthJson(500, [
            'status' => 'error',
            'message' => 'Password reset is temporarily unavailable.'
        ]);
    }
}

// --- ACTION 1: CREATE ACCOUNT ---
if ($action === 'register') {
    $fullNameInput = authPostString('full_name');
    $emailInput = authPostString('email');
    $phoneInput = authPostString('phone');
    $password = authPostString('password');
    $fullName = $fullNameInput !== null ? trim($fullNameInput) : '';
    $email = $emailInput !== null
        ? filter_var(trim($emailInput), FILTER_VALIDATE_EMAIL)
        : false;
    $phone = $phoneInput !== null ? trim($phoneInput) : '';

    if (
        !$fullName ||
        mb_strlen($fullName, 'UTF-8') > 100 ||
        !$email ||
        mb_strlen($email, 'UTF-8') > 254 ||
        !$phone ||
        mb_strlen($phone, 'UTF-8') > 20 ||
        $password === null ||
        strlen($password) < 6 ||
        strlen($password) > 72 ||
        strpos($password, "\0") !== false
    ) {
        respondAuthJson(400, [
            'status' => 'error',
            'message' => 'Please fill all fields correctly. Password must be 6 to 72 bytes.'
        ]);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)");

    try {
        $stmt->execute([$fullName, $email, $phone, $passwordHash]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000' && (int) ($e->errorInfo[1] ?? 0) === 1062) {
            respondAuthJson(409, [
                'status' => 'error',
                'message' => 'An account with this email already exists.'
            ]);
        }

        error_log('Account creation failed: ' . $e->getMessage());
        respondAuthJson(500, [
            'status' => 'error',
            'message' => 'An account could not be created.'
        ]);
    }

    respondAuthJson(201, [
        'status' => 'success',
        'message' => 'Account created! Please log in.'
    ]);
}

// --- ACTION 2: LOGIN ---
if ($action === 'login') {
    $emailInput = authPostString('email');
    $email = $emailInput !== null
        ? filter_var(trim($emailInput), FILTER_VALIDATE_EMAIL)
        : false;
    $password = authPostString('password');

    if (!$email || $password === null || $password === '' || strlen($password) > 72) {
        respondAuthJson(400, [
            'status' => 'error',
            'message' => 'Email and password are required.'
        ]);
    }

    $stmt = $pdo->prepare("SELECT id, full_name, password_hash FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $userRow = $stmt->fetch();

    if (!$userRow || !password_verify($password, $userRow['password_hash'])) {
        respondAuthJson(401, [
            'status' => 'error',
            'message' => 'Invalid email or password.'
        ]);
    }

    startAuthSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userRow['id'];
    $_SESSION['user_name'] = $userRow['full_name'];

    respondAuthJson(200, [
        'status' => 'success',
        'message' => 'Sign-in successful! Redirecting...',
        'redirect' => 'booking.php'
    ]);
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);