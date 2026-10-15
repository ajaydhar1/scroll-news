<?php
/**
 * Coverage Timelines audit (CLI, read-only).
 *   php tools/timeline_audit.php [slug] [--verbose]
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/core/___modules.php';
require_once BASE_PATH . '/core/timelines/___timelines.php';

$args = array_slice($argv, 1);
$verbose = in_array('--verbose', $args, true);
$slug = null;
foreach ($args as $a) {
    if ($a[0] !== '-') {
        $slug = $a;
    }
}

$pdo = getPdo();
$pdo->exec('SET default_transaction_read_only = on');
$problems = 0;

foreach (sn_tl_all_stories() as $story) {
    if ($slug && $story['slug'] !== $slug) {
        continue;
    }
    $rows = sn_tl_fetch_candidates($pdo, $story);
    $model = sn_tl_assemble($story, $rows);
    $s = $model['stats'];
    echo str_repeat('=', 70) . "\n{$story['title']} ({$story['slug']})  candidates=" . count($rows) . "\n";
    printf("core=%d related=%d rejected=%d dupes=%d publishers=%d days=%d peak=%s(%d)\n",
        $s['core_articles'], $s['related_articles'], $s['rejected'], $s['duplicates_removed'],
        $s['publishers'], $s['days_active'], $s['peak_day'], $s['peak_count']);
    printf("top publisher: %s (%.0f%%)\n", $s['top_publisher'], $s['top_publisher_share'] * 100);
    foreach ($model['gaps'] as $g) {
        echo "gap: {$g['days']} quiet days before {$g['before']}\n";
    }

    $byId = [];
    foreach ($model['days'] as $d) {
        foreach ($d['articles'] as $a) {
            $byId[$a['id']] = $a;
        }
    }
    foreach ($story['chapters'] as $i => $ch) {
        $c = $model['chapters'][$i];
        $flag = $c['core_articles'] >= 2 ? 'ok ' : 'LOW';
        if ($c['core_articles'] < 2) {
            $problems++;
        }
        printf("[%s] chapter %d '%s' %s..%s core_articles=%d\n", $flag, $i + 1, $ch['title'], $ch['from'], $ch['to'], $c['core_articles']);
        foreach ($ch['basis'] as $id) {
            $a = $byId[$id] ?? null;
            if (!$a || $a['tier'] !== 'core' || $a['day'] < $ch['from'] || $a['day'] > $ch['to']) {
                $problems++;
                echo "      PROBLEM basis #$id " . ($a ? "tier={$a['tier']} day={$a['day']}" : 'not in timeline') . "\n";
            }
        }
    }
    if ($verbose) {
        foreach ($model['days'] as $d) {
            foreach ($d['articles'] as $a) {
                printf("%s %-7s %-22s c=%-3d %s #%d %s\n", $a['day'], $a['tier'], $a['host'], $a['entity_count'], $a['reason'], $a['id'], $a['title']);
            }
        }
    }
}
echo "\nProblems: $problems\n";
exit($problems ? 1 : 0);
