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

// Normalizes a domain for hostname comparison: lowercase, strips a leading "www."
function publisher_domain_for_matching(string $domain): string
{
    $domain = strtolower(trim($domain));

    return preg_replace('/^www\./', '', $domain) ?? '';
}

// SQL expression that extracts a normalized (lowercase, no "www.") hostname from articles.url,
// so publisher content can be matched by exact host rather than an unsafe substring/LIKE match.
function publisher_article_host_sql_expr(): string
{
    return "regexp_replace(lower(regexp_replace(url, '^[a-zA-Z][a-zA-Z0-9+.-]*://(?:[^@/?#]*@)?([^/?#:]+).*$', '\\1')), '^www\\.', '')";
}

// Loads Content Statistics + the 5 most recent articles for a verified publisher's domain.
// Returns [stats, articles] where stats = ['total_articles', 'analyzed_articles', 'latest_pub_date'].
function publisher_load_content_overview(PDO $pdo, string $domain): array
{
    $hostExpr = publisher_article_host_sql_expr();

    $stats = ['total_articles' => 0, 'analyzed_articles' => 0, 'latest_pub_date' => null];
    $articles = [];

    $statsStmt = $pdo->prepare("
        SELECT COUNT(*) AS total_articles, COUNT(nlp) AS analyzed_articles, MAX(pub_date) AS latest_pub_date
        FROM articles
        WHERE deleted_at IS NULL AND url IS NOT NULL AND $hostExpr = :domain
    ");
    $statsStmt->execute([':domain' => $domain]);
    $row = $statsStmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $stats = [
            'total_articles' => (int) $row['total_articles'],
            'analyzed_articles' => (int) $row['analyzed_articles'],
            'latest_pub_date' => $row['latest_pub_date'],
        ];
    }

    if ($stats['total_articles'] > 0) {
        $listStmt = $pdo->prepare("
            SELECT id, url, title, pub_date, source_slug, nlp
            FROM articles
            WHERE deleted_at IS NULL AND url IS NOT NULL AND $hostExpr = :domain
            ORDER BY pub_date DESC NULLS LAST
            LIMIT 5
        ");
        $listStmt->execute([':domain' => $domain]);

        foreach ($listStmt->fetchAll(PDO::FETCH_ASSOC) as $articleRow) {
            $pubTs = $articleRow['pub_date'] ? strtotime((string) $articleRow['pub_date']) : false;
            $category = $articleRow['source_slug'] ? ucfirst((string) $articleRow['source_slug']) : '';

            // Same newsroom.php analyze link convention used on article cards elsewhere on the site.
            $analyzeUrl = '/newsroom.php?' . http_build_query([
                'url' => (string) $articleRow['url'],
                'category' => $category,
                'pub_date' => $pubTs !== false ? $pubTs : '',
                'db' => 1,
            ]);

            $articles[] = [
                'title' => $articleRow['title'],
                'url' => $articleRow['url'],
                'pub_date_human' => sn_format_pub_date($articleRow['pub_date']),
                'is_analyzed' => !empty($articleRow['nlp']),
                'analyze_url' => $analyzeUrl,
            ];
        }
    }

    return [$stats, $articles];
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