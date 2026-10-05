<?php

function sn_community_trail_editor_emails(): array
{
    return [
        'ajaytest2@sharklasers.com',
    ];
}

function sn_community_trail_sharing_enabled(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT community_trail_sharing FROM users WHERE id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $userId]);
    $value = $stmt->fetchColumn();

    return $value === true || in_array(strtolower((string) $value), ['1', 't', 'true'], true);
}

function sn_set_community_trail_sharing(PDO $pdo, int $userId, bool $enabled): void
{
    $stmt = $pdo->prepare('UPDATE users SET community_trail_sharing = :enabled WHERE id = :user_id');
    $stmt->bindValue(':enabled', $enabled, PDO::PARAM_BOOL);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
}

function sn_trail_playback_authorized(
    PDO $pdo,
    string $base,
    int $ownerId,
    string $ownerEmail,
    ?int $viewerId
): bool {
    $isEditor = in_array(
        strtolower(trim($ownerEmail)),
        array_map('strtolower', sn_community_trail_editor_emails()),
        true
    );

    if ($base === 'personal') {
        return $viewerId !== null && $viewerId === $ownerId;
    }

    if ($base === 'editors') {
        return $isEditor;
    }

    if ($base === 'community') {
        return !$isEditor && sn_community_trail_sharing_enabled($pdo, $ownerId);
    }

    return false;
}

function sn_community_trail_date_is_recent(string $trailDate, ?DateTimeImmutable $now = null): bool
{
    $timezone = new DateTimeZone('UTC');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $trailDate, $timezone);
    $errors = DateTimeImmutable::getLastErrors();

    if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return false;
    }

    $today = ($now ?? new DateTimeImmutable('now', $timezone))->setTimezone($timezone)->setTime(0, 0);
    $cutoff = $today->modify('-2 months');

    return $date >= $cutoff && $date <= $today;
}

function sn_community_trail_csrf_token(): string
{
    if (empty($_SESSION['community_trail_csrf'])) {
        $_SESSION['community_trail_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['community_trail_csrf'];
}