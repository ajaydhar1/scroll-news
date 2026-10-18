<?php
// Scroll Strip domain filtering: regex semantics (PCRE) and, when SN_TEST_PG_DSN is set,
// the real panel include against Postgres. Example:
//   SN_TEST_PG_DSN='pgsql:host=/tmp/pgt;port=55432;dbname=postgres' php tests/scroll_strip_domain_filter_test.php

// Load only the helpers under test (the full module needs app config and a DB).
$src = file_get_contents(__DIR__ . '/../core/___modules.php');
foreach (['/function buildNotILikeNamed.*?\n}\n/s', '/const SCROLL_STRIP_EXCLUDED_HOSTS = \[.*?\n\];/s', '/function scrollStripHostExclusionRegex.*?\n}\n/s'] as $re) {
    if (!preg_match($re, $src, $m)) { fwrite(STDERR, "missing helper $re\n"); exit(1); }
    eval($m[0]);
}

$fail = 0;
$check = function (string $label, bool $ok) use (&$fail) { if (!$ok) { echo "FAIL $label\n"; $fail++; } };

$re = '~' . scrollStripHostExclusionRegex('www.uco.es') . '~i';
foreach ([
    'https://www.uco.es/noticia' => true, 'https://UCO.ES/x' => true, 'http://WWW.UCO.ES./x' => true,
    'https://sub.uco.es/x' => true, 'https://a.b.uco.es' => true, 'https://uco.es:8080/x?y' => true,
    'https://user@www.uco.es/x' => true,
    'https://notuco.es/x' => false, 'https://uco.es.evil.com/x' => false, 'https://example.com/www.uco.es' => false,
    'https://example.com/?u=https://uco.es/' => false, 'https://uco.com/x' => false, 'https://ucoxes/' => false,
] as $url => $exp) {
    $check("regex $url", (bool) preg_match($re, $url) === $exp);
}
$check('regex has no wildcard from dots', !preg_match('~' . scrollStripHostExclusionRegex('uco.es') . '~i', 'https://ucoXes/'));

$dsn = getenv('SN_TEST_PG_DSN');
if ($dsn) {
    $pdo = new PDO($dsn, getenv('SN_TEST_PG_USER') ?: null, getenv('SN_TEST_PG_PASS') ?: null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('DROP TABLE IF EXISTS articles');
    $pdo->exec('CREATE TABLE articles (id serial primary key, url text, title text, description text, source_slug text, media_url text, pub_date timestamptz, nlp jsonb, deleted_at timestamptz)');
    $ins = $pdo->prepare("INSERT INTO articles (url,title,source_slug,media_url,pub_date,nlp) VALUES (?,?,?,?,now() - (?::int * interval '1 minute'),'{\"entities\":[{\"text\":\"x\",\"count\":1}]}')");
    $n = 0;
    $add = function (string $url) use ($ins, &$n) { $ins->execute([$url, "t$n", 's', 'http://img/x.jpg', $n]); $n++; };
    // 70 newest rows are all excluded or duplicates-of-excluded; 14 good ones sit behind them.
    for ($i = 0; $i < 35; $i++) { $add("https://www.uco.es/n$i"); $add("https://www.nytimes.com/n$i"); }
    $add('https://sub.uco.es/x'); $add('https://notuco.es/ok');
    for ($i = 0; $i < 14; $i++) { $add("https://example$i.com/story"); }

    $GLOBALS['filter_out'] = ['usatoday', 'nytimes', 'reuters'];
    function _pdo_or_null(): ?PDO { return $GLOBALS['pdo']; }
    function scroll_get_article_badges(array $a): array { return []; }
    function sn_normalized_emotion_distribution(array $e, int $n): array { return []; }
    define('BASE_PATH', dirname(__DIR__));
    ob_start();
    include __DIR__ . '/../views/home/panels/___scroll_strip.php';
    ob_end_clean();
    $urls = array_column($scroll_strip_articles, 'url');
    $check('12 slots filled despite 70 excluded rows ahead (got ' . count($urls) . ')', count($urls) === 12);
    $check('no uco.es / subdomain', !array_filter($urls, fn($u) => preg_match('~://([^/]*\.)?uco\.es~', $u)));
    $check('no filter_out entries', !array_filter($urls, fn($u) => stripos($u, 'nytimes') !== false));
    $check('notuco.es kept', in_array('https://notuco.es/ok', $urls, true));
    echo "DB check ran: " . count($urls) . " urls\n";
} else {
    echo "SN_TEST_PG_DSN not set; skipped DB check\n";
}
echo $fail ? "FAILED\n" : "OK\n";
exit($fail ? 1 : 0);
