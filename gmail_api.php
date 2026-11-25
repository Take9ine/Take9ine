<?php

declare(strict_types=1);

/**
 * Send an email through the Gmail API using OAuth 2.0 refresh-token credentials.
 *
 * Configure GMAIL_CLIENT_ID, GMAIL_CLIENT_SECRET, and GMAIL_REFRESH_TOKEN in
 * the PHP process environment. The OAuth refresh token
 * must be authorized for the https://www.googleapis.com/auth/gmail.send scope.
 */
function sendGmailApiMessage(
    string $email,
    string $subject,
    string $htmlBody,
    string $textBody
): bool {
    $clientId = trim((string) getenv('GMAIL_CLIENT_ID'));
    $clientSecret = trim((string) getenv('GMAIL_CLIENT_SECRET'));
    $refreshToken = trim((string) getenv('GMAIL_REFRESH_TOKEN'));
    $senderEmail = 'take9ine254@gmail.com';

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !filter_var($senderEmail, FILTER_VALIDATE_EMAIL) ||
        $clientId === '' ||
        $clientSecret === '' ||
        $refreshToken === ''
    ) {
        error_log('Gmail API is not configured or the recipient/sender email is invalid.');
        return false;
    }

    if (!function_exists('curl_init')) {
        error_log('Gmail API delivery requires the PHP cURL extension.');
        return false;
    }

    $tokenResponse = gmailApiRequest(
        'https://oauth2.googleapis.com/token',
        [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ],
        ['Content-Type: application/x-www-form-urlencoded']
    );

    if (
        $tokenResponse === null ||
        !isset($tokenResponse['access_token']) ||
        !is_string($tokenResponse['access_token'])
    ) {
        error_log('Gmail API OAuth token refresh failed.');
        return false;
    }

    $boundary = 'take9ine_' . bin2hex(random_bytes(12));
    $rawMessage = implode("\r\n", [
        'From: T@ke9ine <' . $senderEmail . '>',
        'To: ' . $email,
        'Subject: ' . str_replace(["\r", "\n"], '', $subject),
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        '',
        '--' . $boundary,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        chunk_split(base64_encode($textBody)),
        '--' . $boundary,
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        chunk_split(base64_encode($htmlBody)),
        '--' . $boundary . '--',
        '',
    ]);

    $messageResponse = gmailApiRequest(
        'https://gmail.googleapis.com/gmail/v1/users/me/messages/send',
        ['raw' => rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=')],
        [
            'Authorization: Bearer ' . $tokenResponse['access_token'],
            'Content-Type: application/json',
        ]
    );

    if ($messageResponse === null || !isset($messageResponse['id'])) {
        error_log('Gmail API message delivery failed.');
        return false;
    }

    return true;
}

/**
 * Make a JSON or form-encoded request to a Google OAuth/Gmail endpoint.
 *
 * @param array<string, string> $headers
 * @return array<string, mixed>|null
 */
function gmailApiRequest(string $url, array $data, array $headers): ?array
{
    $isFormRequest = in_array(
        'Content-Type: application/x-www-form-urlencoded',
        $headers,
        true
    );
    $requestBody = $isFormRequest
        ? http_build_query($data)
        : json_encode($data);

    if (!is_string($requestBody)) {
        error_log('Could not encode a Gmail API HTTP request.');
        return null;
    }

    $handle = curl_init($url);

    if ($handle === false) {
        error_log('Could not initialize a Gmail API HTTP request.');
        return null;
    }

    curl_setopt_array($handle, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $requestBody,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($handle);
    $statusCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);

    if ($response === false) {
        error_log('Gmail API HTTP request failed: ' . curl_error($handle));
        curl_close($handle);
        return null;
    }

    curl_close($handle);

    if ($statusCode < 200 || $statusCode >= 300) {
        $errorDetails = json_decode($response, true);
        $errorMessage = is_array($errorDetails)
            ? ($errorDetails['error_description'] ?? $errorDetails['error']['message'] ?? $errorDetails['error'] ?? '')
            : '';
        if (!is_string($errorMessage)) {
            $errorMessage = '';
        }
        error_log(
            'Gmail API returned HTTP status ' . $statusCode . '. ' .
            substr($errorMessage, 0, 500)
        );
        return null;
    }

    $decoded = json_decode($response, true);

    return is_array($decoded) ? $decoded : null;
}
