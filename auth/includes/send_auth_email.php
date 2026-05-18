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

function build_auth_email_html(string $title, string $intro, string $buttonText, string $url, string $extraNote = ''): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeIntro = htmlspecialchars($intro, ENT_QUOTES, 'UTF-8');
    $safeButtonText = htmlspecialchars($buttonText, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $safeExtraNote = $extraNote ? '<p style="margin: 24px 0 0; color: #6c757d; font-size: 14px; line-height: 1.6;">' . htmlspecialchars($extraNote, ENT_QUOTES, 'UTF-8') . '</p>' : '';

    return '
        <div style="margin: 0; padding: 32px 16px; background: #eef1f4; font-family: Arial, Helvetica, sans-serif; color: #212529;">
            <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 18px; overflow: hidden; border: 1px solid #dde3ea;">
                
                <div style="padding: 28px 32px; background: #343a40; color: #ffffff;">
                    <div style="font-size: 20px; font-weight: 700; letter-spacing: -0.02em;">
                        Scroll News
                    </div>
                    <div style="margin-top: 6px; font-size: 13px; color: #d7dee6;">
                        AI-powered news organization
                    </div>
                </div>

                <div style="padding: 34px 32px;">
                    <h1 style="margin: 0 0 14px; font-size: 24px; line-height: 1.25; color: #212529;">
                        ' . $safeTitle . '
                    </h1>

                    <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #495057;">
                        ' . $safeIntro . '
                    </p>

                    <p style="margin: 0 0 28px;">
                        <a href="' . $safeUrl . '" style="display: inline-block; padding: 13px 22px; background: #198754; color: #ffffff; text-decoration: none; border-radius: 999px; font-size: 15px; font-weight: 700;">
                            ' . $safeButtonText . '
                        </a>
                    </p>

                    <p style="margin: 0 0 8px; color: #6c757d; font-size: 14px; line-height: 1.6;">
                        If the button does not work, copy and paste this link into your browser:
                    </p>

                    <p style="margin: 0; word-break: break-all; font-size: 13px; line-height: 1.6;">
                        <a href="' . $safeUrl . '" style="color: #198754; text-decoration: underline;">' . $safeUrl . '</a>
                    </p>

                    ' . $safeExtraNote . '
                </div>

                <div style="padding: 20px 32px; background: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <p style="margin: 0; color: #868e96; font-size: 12px; line-height: 1.5;">
                        This message was sent by Scroll News. Please do not reply to this email.
                    </p>
                </div>
            </div>
        </div>
    ';
}