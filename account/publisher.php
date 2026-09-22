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

// Same Google-hosted favicon service used for domain/publisher logos elsewhere on the site.
function publisher_favicon_url(string $domain, int $size = 64): string
{
    return 'https://t0.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=' . rawurlencode('http://' . $domain) . '&size=' . $size;
}

function publisher_normalize_url(string $url): ?string
{
    $url = trim($url);

    if ($url === '' || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }

    return $url;
}

function publisher_profile_url_fields(): array
{
    return [
        'website_url' => 'Website',
        'rss_url' => 'RSS Feed',
        'x_url' => 'X (Twitter)',
        'instagram_url' => 'Instagram',
        'facebook_url' => 'Facebook',
        'linkedin_url' => 'LinkedIn',
        'youtube_url' => 'YouTube',
        'tiktok_url' => 'TikTok',
        'threads_url' => 'Threads',
    ];
}

function publisher_find_verified(PDO $pdo, int $publisherId, int $userId): ?array
{
    $stmt = $pdo->prepare("SELECT p.id, p.domain, p.name FROM publishers p INNER JOIN publisher_users pu ON pu.publisher_id = p.id WHERE p.id = :publisher_id AND pu.user_id = :user_id AND pu.status = 'verified' LIMIT 1");
    $stmt->execute([':publisher_id' => $publisherId, ':user_id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function publisher_empty_profile(): array
{
    return array_fill_keys(
        array_merge(['description', 'location'], array_keys(publisher_profile_url_fields())),
        null
    );
}

function publisher_load_profile(PDO $pdo, int $publisherId): array
{
    $stmt = $pdo->prepare('SELECT description, location, website_url, rss_url, x_url, instagram_url, facebook_url, linkedin_url, youtube_url, tiktok_url, threads_url FROM publisher_profiles WHERE publisher_id = :publisher_id LIMIT 1');
    $stmt->execute([':publisher_id' => $publisherId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: publisher_empty_profile();
}