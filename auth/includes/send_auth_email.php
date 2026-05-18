<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Auth Email Helper
|--------------------------------------------------------------------------
|
| Sends transactional auth emails through Resend.
|
*/

function send_auth_email(string $to, string $subject, string $html): bool
{
    $apiKey = getenv('RESEND_API_KEY');

    if (!$apiKey) {
        error_log('[AuthEmail] RESEND_API_KEY is missing.');
        return false;
    }

    $payload = [
        'from' => 'Scroll News <noreply@scrollnews.ai>',
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
    ];

    $ch = curl_init('https://api.resend.com/emails');

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false || $statusCode < 200 || $statusCode >= 300) {
        error_log('[AuthEmail] Failed to send email.');
        error_log('[AuthEmail] Status: ' . $statusCode);
        error_log('[AuthEmail] cURL Error: ' . $curlError);
        error_log('[AuthEmail] Response: ' . $response);

        return false;
    }

    error_log('[AuthEmail] Email sent to ' . $to . ' with subject: ' . $subject);

    return true;
}