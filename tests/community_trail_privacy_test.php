<?php

require_once __DIR__ . '/../core/community_trail_privacy.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT NOT NULL, community_trail_sharing BOOLEAN NOT NULL DEFAULT 0)');
$migration = file_get_contents(__DIR__ . '/../database/schema/004_community_trail_sharing.sql');
if (!str_contains($migration, 'ADD COLUMN community_trail_sharing BOOLEAN NOT NULL DEFAULT FALSE')) {
    fwrite(STDERR, 'FAIL: migration must add an opt-in preference defaulting to false' . PHP_EOL);
    exit(1);
}
$pdo->exec("INSERT INTO users (id, email, community_trail_sharing) VALUES
    (1, 'reader@example.com', TRUE),
    (2, 'private@example.com', FALSE),
    (3, 'ajaytest2@sharklasers.com', TRUE)");
$pdo->exec("INSERT INTO users (id, email) VALUES (4, 'default@example.com')");

$checks = [
    'community sharing allows signed-out visitors when enabled' => sn_trail_playback_authorized($pdo, 'community', 1, 'reader@example.com', null),
    'community sharing denies access when disabled' => !sn_trail_playback_authorized($pdo, 'community', 2, 'private@example.com', null),
    'community sharing denies editor accounts' => !sn_trail_playback_authorized($pdo, 'community', 3, 'ajaytest2@sharklasers.com', null),
    'personal trail allows its authenticated owner' => sn_trail_playback_authorized($pdo, 'personal', 1, 'reader@example.com', 1),
    'personal trail denies another authenticated account' => !sn_trail_playback_authorized($pdo, 'personal', 1, 'reader@example.com', 2),
    'personal trail denies signed-out visitors' => !sn_trail_playback_authorized($pdo, 'personal', 1, 'reader@example.com', null),
    'editor trail allows an intended editor account' => sn_trail_playback_authorized($pdo, 'editors', 3, 'ajaytest2@sharklasers.com', null),
    'editor trail denies accounts outside the editor allowlist' => !sn_trail_playback_authorized($pdo, 'editors', 1, 'reader@example.com', null),
    'unknown base grants no access' => !sn_trail_playback_authorized($pdo, 'unknown', 1, 'reader@example.com', 1),
    'new preference defaults to off' => !sn_community_trail_sharing_enabled($pdo, 4),
    'community date at the two-month boundary is accepted' => sn_community_trail_date_is_recent('2026-08-05', new DateTimeImmutable('2026-10-05 12:00:00', new DateTimeZone('UTC'))),
    'community date before the public window is rejected' => !sn_community_trail_date_is_recent('2026-08-04', new DateTimeImmutable('2026-10-05 12:00:00', new DateTimeZone('UTC'))),
    'future community date is rejected' => !sn_community_trail_date_is_recent('2026-10-06', new DateTimeImmutable('2026-10-05 12:00:00', new DateTimeZone('UTC'))),
    'invalid calendar date is rejected' => !sn_community_trail_date_is_recent('2026-02-30', new DateTimeImmutable('2026-10-05 12:00:00', new DateTimeZone('UTC'))),
];

sn_set_community_trail_sharing($pdo, 2, true);
$checks['enabling sharing permits community playback'] = sn_trail_playback_authorized($pdo, 'community', 2, 'private@example.com', null);

sn_set_community_trail_sharing($pdo, 2, false);
$checks['disabling sharing immediately blocks community playback'] = !sn_trail_playback_authorized($pdo, 'community', 2, 'private@example.com', null);

$accountPage = file_get_contents(__DIR__ . '/../account/index.php');
$trailsPage = file_get_contents(__DIR__ . '/../news-trails.php');
$playerPage = file_get_contents(__DIR__ . '/../news-trail-player.php');
$checks['account page exposes the shared preference form'] = str_contains($accountPage, 'name="community_trail_sharing"');
$checks['News Trails page exposes the shared preference form'] = str_contains($trailsPage, 'name="community_trail_sharing"');
$checks['Community listing requires current opt-in'] = str_contains($trailsPage, 'u.community_trail_sharing IS TRUE');
$checks['player enforces owner authorization'] = str_contains($playerPage, 'sn_trail_playback_authorized(');
$checks['player enforces Community date window and item threshold'] = str_contains($playerPage, 'sn_community_trail_date_is_recent($trailDate)')
    && str_contains($playerPage, 'count($trailItems) < 3');

foreach ($checks as $description => $passed) {
    if (!$passed) {
        fwrite(STDERR, 'FAIL: ' . $description . PHP_EOL);
        exit(1);
    }
}

echo 'PASS: ' . count($checks) . ' Community Trail privacy checks' . PHP_EOL;