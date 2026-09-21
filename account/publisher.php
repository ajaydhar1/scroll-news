<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/includes/auth_db.php';
require_once __DIR__ . '/../auth/includes/send_auth_email.php';

function publisher_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function publisher_normalize_domain(string $domain): ?string
{
    $domain = strtolower(trim($domain));
    $domain = rtrim($domain, '.');

    if ($domain === '' || strlen($domain) > 253 || !preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/', $domain)) {
        return null;
    }

    return $domain;
}

function publisher_normalize_email(string $email): ?string
{
    $email = trim($email);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        return null;
    }

    return strtolower($email);
}

function publisher_verification_email(string $domain, string $url): string
{
    return build_auth_email_html(
        'Verify publisher access',
        'Confirm that you are authorized to manage ' . $domain . ' on Scroll News.',
        'Verify Publisher',
        $url,
        'This link expires in 24 hours and can only be used once.'
    );
}