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
    $meta = [];
    $rows = sn_tl_fetch_candidates($pdo, $story, $meta);
    $model = sn_tl_assemble($story, $rows, $meta);
    $s = $model['stats'];
    $active = ($story['status'] ?? '') === 'active';
    echo str_repeat('=', 70) . "\n{$story['title']} ({$story['slug']}, {$story['status']})  candidates=" . count($rows) . "\n";
    printf("core=%d related=%d rejected=%d dupes=%d publishers=%d days=%d peak=%s(%d)\n",
        $s['core_articles'], $s['related_articles'], $s['rejected'], $s['duplicates_removed'],
        $s['publishers'], $s['days_active'], $s['peak_day'], $s['peak_count']);
    printf("top publisher: %s (%.0f%%)\n", $s['top_publisher'], $s['top_publisher_share'] * 100);
    $units = array_unique(array_column($model['series'], 'unit'));
    printf("window=%s..%s open=%s  series=%s buckets=%d  latest_core=%d uncovered_core=%d\n",
        $meta['window']['from'] ?? '?', $meta['window']['to'] ?? '?', !empty($meta['window']['open']) ? 'yes' : 'no',
        implode('/', $units), count($model['series']), $s['latest_core'] ?? 0, $s['uncovered_core'] ?? 0);
    $limit = (int)($meta['limit'] ?? 0);
    printf("fetch: %d rows of limit %d (%.0f%% of cap)%s\n", $meta['fetched'] ?? count($rows), $limit,
        $limit ? 100 * ($meta['fetched'] ?? count($rows)) / $limit : 0,
        !empty($meta['truncated']) ? '  TRUNCATED, reliable from ' . ($meta['coverage_from'] ?? '?') : '');
    if (!empty($meta['truncated'])) {
        $problems++;
        echo "      PROBLEM candidate limit reached; raise max_candidates or narrow the matching rules\n";
    } elseif ($limit && ($meta['fetched'] ?? 0) > 0.7 * $limit) {
        echo "      WARN candidate set is over 70% of max_candidates\n";
    }
    if (!empty($s['trimmed'])) {
        echo "      WARN {$s['trimmed']} items trimmed by max_items\n";
    }
    if ($active) {
        if (!empty($story['window']['to'])) {
            $problems++;
            echo "      PROBLEM active story has a fixed window.to; it will not self-update\n";
        }
        $newest = $model['days'] ? end($model['days'])['date'] : null;
        $age = $newest ? (int)((strtotime(sn_tl_today()) - strtotime($newest)) / 86400) : null;
        printf("newest article day: %s (%s days ago)\n", $newest ?? 'none', $age ?? '?');
        if ($age === null || $age > 3) {
            echo "      WARN newest matching article is stale; check ingestion or matching rules\n";
        }
    }
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
