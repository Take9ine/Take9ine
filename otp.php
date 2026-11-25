<?php

declare(strict_types=1);

/**
 * Create a persisted OTP challenge and return its ID and plaintext code.
 *
 * @return array{challenge_id: string, code: string}
 */
function createOtpChallenge(PDO $pdo, string $purpose, ?int $subjectId = null): array
{
    $challengeId = bin2hex(random_bytes(32));
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = time() + 600;

    $cleanup = $pdo->prepare('DELETE FROM otp_verifications WHERE expires_at <= ?');
    $cleanup->execute([time()]);

    $insert = $pdo->prepare(
        'INSERT INTO otp_verifications (
            challenge_id,
            purpose,
            subject_id,
            code_hash,
            expires_at,
            failed_attempts
        ) VALUES (?, ?, ?, ?, ?, 0)'
    );
    $insert->execute([
        $challengeId,
        $purpose,
        $subjectId,
        password_hash($code, PASSWORD_DEFAULT),
        $expiresAt,
    ]);

    return [
        'challenge_id' => $challengeId,
        'code' => $code,
    ];
}

/**
 * Verify a challenge, atomically consume valid codes, and count bad attempts.
 *
 * @return 'verified'|'invalid'|'expired'|'locked'|'missing'
 */
function verifyOtpChallenge(
    PDO $pdo,
    string $challengeId,
    string $purpose,
    string $code,
    ?int $subjectId = null
): string {
    $pdo->beginTransaction();

    try {
        $lockClause = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? ' FOR UPDATE'
            : '';
        $select = $pdo->prepare(
            'SELECT code_hash, expires_at, failed_attempts
             FROM otp_verifications
             WHERE challenge_id = ? AND purpose = ?
               AND (subject_id = ? OR (subject_id IS NULL AND ? IS NULL))'
            . $lockClause
        );
        $select->execute([$challengeId, $purpose, $subjectId, $subjectId]);
        $challenge = $select->fetch();

        if (!$challenge) {
            $pdo->commit();
            return 'missing';
        }

        if ((int) $challenge['expires_at'] <= time()) {
            $delete = $pdo->prepare('DELETE FROM otp_verifications WHERE challenge_id = ?');
            $delete->execute([$challengeId]);
            $pdo->commit();
            return 'expired';
        }

        if ((int) $challenge['failed_attempts'] >= 5) {
            $delete = $pdo->prepare('DELETE FROM otp_verifications WHERE challenge_id = ?');
            $delete->execute([$challengeId]);
            $pdo->commit();
            return 'locked';
        }

        if (
            !preg_match('/^\d{6}$/', $code) ||
            !password_verify($code, (string) $challenge['code_hash'])
        ) {
            $attempts = (int) $challenge['failed_attempts'] + 1;
            if ($attempts >= 5) {
                $delete = $pdo->prepare('DELETE FROM otp_verifications WHERE challenge_id = ?');
                $delete->execute([$challengeId]);
                $pdo->commit();
                return 'locked';
            }

            $update = $pdo->prepare(
                'UPDATE otp_verifications
                 SET failed_attempts = ?
                 WHERE challenge_id = ?'
            );
            $update->execute([$attempts, $challengeId]);
            $pdo->commit();
            return 'invalid';
        }

        $delete = $pdo->prepare('DELETE FROM otp_verifications WHERE challenge_id = ?');
        $delete->execute([$challengeId]);
        $pdo->commit();

        return 'verified';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function deleteOtpChallenge(PDO $pdo, string $challengeId): void
{
    $delete = $pdo->prepare('DELETE FROM otp_verifications WHERE challenge_id = ?');
    $delete->execute([$challengeId]);
}
