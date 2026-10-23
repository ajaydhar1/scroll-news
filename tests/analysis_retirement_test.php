<?php

define('BASE_PATH', dirname(__DIR__));

function retirement_child(string $code): array
{
    $process = proc_open([PHP_BINARY, '-r', $code], [
        0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start offline retirement probe.');
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0) {
        throw new RuntimeException('Offline probe failed: ' . $stderr);
    }
    $marker = strrpos($stdout, 'RETIREMENT_RESULT:');
    if ($marker === false) {
        throw new RuntimeException('Offline probe returned no result.');
    }
    return json_decode(substr($stdout, $marker + strlen('RETIREMENT_RESULT:')), true, 512, JSON_THROW_ON_ERROR);
}

$checks = 0;
$failures = [];
$assert = static function (bool $ok, string $message) use (&$checks, &$failures): void {
    $checks++;
    if (!$ok) {
        $failures[] = $message;
    }
};
$endpoint = BASE_PATH . '/analysis.php';
$source = file_get_contents($endpoint);

foreach (['GET', 'HEAD'] as $method) {
    foreach ([
        '',
        'context=entity&value=Trump&w=24h',
        'context=pub&value=example.com&w=30d',
        'context=narrative_frame&value=conflict&w=7d',
        'context=sent&value=negative&w=custom&from=2000-01-01&to=2099-01-01',
        'context=category&value=politics',
        'enable=1&preview=1&legacy=1&nocache=1',
        'context[]=entity&w[]=custom&value[]=x',
    ] as $query) {
        $setup = '$_SERVER["REQUEST_METHOD"] = ' . var_export($method, true) . ';'
            . '$_SERVER["REQUEST_URI"] = ' . var_export('/analysis.php?' . $query, true) . ';'
            . 'parse_str(' . var_export($query, true) . ', $_GET);'
            . '$_COOKIE["scroll_news_session"] = "synthetic-offline-cookie";';
        $result = retirement_child($setup . '
ob_start();
register_shutdown_function(static function (): void {
    $body = ob_get_clean();
    echo "RETIREMENT_RESULT:" . json_encode([
        "status" => http_response_code(),
        "body" => $body,
        "files" => get_included_files(),
        "session" => session_status(),
    ]);
});
require ' . var_export($endpoint, true) . ';');
        $assert($result['status'] === 410, 'Every legacy parameter variant returns 410: ' . $method);
        $assert(count($result['files']) === 1 && realpath($result['files'][0]) === realpath($endpoint), 'No bootstrap or modules loaded');
        $assert($result['session'] === PHP_SESSION_NONE, 'No authentication session initialized');
        $assert($method === 'HEAD' ? $result['body'] === '' : str_contains($result['body'], 'href="/search.php"') && str_contains($result['body'], 'href="/newsroom.php"'), 'GET has helpful links; HEAD has no body');
        $assert(strlen($result['body']) < 2500, 'Retirement response stays lightweight');
    }
}

// Exercise the exact source with header capture; CLI does not expose response headers.
$headerProbe = 'namespace RetirementHeaders;
function header(string $value): void { $GLOBALS["captured_headers"][] = $value; }
function http_response_code(int $value): void { $GLOBALS["captured_status"] = $value; }
$_SERVER["REQUEST_METHOD"] = "GET";
ob_start();
eval(' . var_export('?>' . preg_replace('/^<\?php/', '<?php namespace RetirementHeaders;', $source, 1), true) . ');
ob_end_clean();
echo "RETIREMENT_RESULT:" . json_encode(["headers" => $GLOBALS["captured_headers"], "status" => $GLOBALS["captured_status"]]);';
$headers = retirement_child($headerProbe);
$assert($headers['status'] === 410, 'Header probe returns Gone');
foreach (['Content-Type: text/html; charset=utf-8', 'Cache-Control: no-store', 'X-Robots-Tag: noindex'] as $header) {
    $assert(in_array($header, $headers['headers'], true), 'Response header: ' . $header);
}
$assert(!preg_match('/\b(require|include|PDO|_pdo_or_null|auth_db|eval)\b/', $source), 'Endpoint has no database/bootstrap/evaluation path');

require_once BASE_PATH . '/core/___modules.php';

$term = '  AT&T / café + "news" #1?  ';
parse_str(parse_url(sn_search_url($term), PHP_URL_QUERY), $searchParams);
$assert($searchParams === ['q' => trim($term), 'mode' => 'classic'], 'Search terms round-trip with reserved characters and Unicode');
$assert(!str_contains(sn_search_url($term), 'analysis.php'), 'Search builder has no legacy destination');

$article = [
    'id' => 1,
    'title' => 'An offline article',
    'description' => 'An offline description.',
    'url' => 'https://example.com/story',
    'link' => 'https://example.com/story',
    'feed_name' => 'politics',
    'source_slug' => 'politics',
    'pub_date' => '2026-10-22T16:00:00Z',
    'nlp' => json_encode([
        'entities' => [['text' => 'AT&T', 'label' => 'ORG', 'count' => 3]],
        'keywords' => ['AT&T'],
        'topics' => ['conflict' => 0.8],
        'sentiment' => ['score' => 0.2],
    ]),
];
$vm = sn_article_vm_from_row($article, ['force_analyze' => true, 'force_db' => true]);
$assert($vm['category_url'] === null, 'Category labels are not misleading keyword searches');
$assert(str_starts_with($vm['analyze_url'], '/newsroom.php?'), 'Individual article analysis remains in Newsroom');
ob_start();
sn_render_article_card($vm);
$card = ob_get_clean();
ob_start();
sn_render_article_card_archive($vm);
$archive = ob_get_clean();
$publisher = scroll_render_article_intel_item($article);
foreach (['Search card' => $card, 'Archive card' => $archive, 'Publisher card' => $publisher] as $name => $html) {
    $assert(!str_contains($html, 'analysis.php'), $name . ' has no retired links');
    $assert(str_contains($html, '/search.php?q=AT%26T&amp;mode=classic') || str_contains($html, '/search.php?q=at%26t&amp;mode=classic'), $name . ' has encoded entity searches');
    $assert(str_contains($html, '/newsroom.php?'), $name . ' keeps article analysis');
}
$assert(str_contains($publisher, '<span class="nlp-chip nlp-chip-frame">'), 'Narrative frames render as text');

// Load only pure panel render functions, not its top-level query/cache/RSS work.
$tokens = token_get_all(file_get_contents(BASE_PATH . '/views/home/panels/___news_intel_panel.php'));
for ($i = 0, $length = count($tokens); $i < $length; $i++) {
    if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
        continue;
    }
    $start = $i;
    while (isset($tokens[++$i]) && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {}
    if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_STRING || !str_starts_with($tokens[$i][1], 'sn_nip_')) {
        continue;
    }
    $function = '';
    $depth = 0;
    $opened = false;
    for ($i = $start; $i < $length; $i++) {
        $token = $tokens[$i];
        $function .= is_array($token) ? $token[1] : $token;
        if ($token === '{') {
            $opened = true;
            $depth++;
        } elseif ($token === '}' && --$depth === 0 && $opened) {
            break;
        }
    }
    eval($function);
}
$lead = sn_nip_render_lead($article, 'neutral');
$assert(str_contains($lead, '/search.php?q=AT%26T&amp;mode=classic'), 'News Intelligence entities search headlines');
$assert(str_contains($lead, '<span class="sn-nip-chip is-frame">conflict</span>'), 'News Intelligence frames remain non-clickable');
$chipCss = file_get_contents(BASE_PATH . '/assets/css/panels/news-intel-signal-map.css');
$assert(preg_match('/\.sn-nip a\.sn-nip-chip,\s*\.sn-nip span\.sn-nip-chip \{/', $chipCss) === 1, 'Static frames share the original chip geometry and typography');
$assert(preg_match('/\.sn-nip a\.sn-nip-chip\.is-frame,\s*\.sn-nip span\.sn-nip-chip\.is-frame \{/', $chipCss) === 1, 'Static frames share the original narrative colors');
$assert(str_contains($chipCss, '.sn-nip span.sn-nip-chip { cursor: default; }'), 'Static frames use a non-interactive cursor');
$assert(str_contains($chipCss, '.sn-nip a.sn-nip-chip, .sn-nip span.sn-nip-chip { min-height: 44px; }'), 'Static frames preserve mobile chip sizing');
$assert(!preg_match('/span\.sn-nip-chip[^{]*(?::hover|:focus)/', $chipCss), 'Static frames have no link hover or focus rules');
$assert(!str_contains($lead, 'analysis.php'), 'News Intelligence lead has no retired links');

$stageData = [
    'id' => 'offline', 'n' => 0, 'count' => 2, 'hours' => array_fill(0, 24, 0),
    'tone' => ['positive' => 0, 'neutral' => 2, 'negative' => 0],
    'articles' => [], 'related' => [],
];
foreach (['entities', 'places', 'topics'] as $lens) {
    $stage = sn_nip_render_stage(
        ['lens' => $lens, 'label' => 'AT&T'] + $stageData,
        [$lens => ['label' => 'Offline signals']]
    );
    $assert(!str_contains($stage, 'analysis.php'), 'News Intelligence stage has no retired destination: ' . $lens);
    $assert(
        $lens === 'topics'
            ? !str_contains($stage, 'href=')
            : str_contains($stage, '/search.php?q=AT%26T&amp;mode=classic'),
        'Signal headings use meaningful Search or plain text: ' . $lens
    );
}

$navPath = BASE_PATH . '/views/partials/___topnav_product.php';
$navSource = file_get_contents($navPath);
$navSource = str_replace("require_once __DIR__ . '/../../auth/includes/auth_bootstrap.php';", '', $navSource);
$navSource = str_replace('__DIR__', var_export(dirname($navPath), true), $navSource);
$currentUser = null;
$_GET = [];
ob_start();
eval('?>' . $navSource);
$nav = ob_get_clean();
ob_start();
require BASE_PATH . '/views/home/partials/___home_modules.php';
$modules = ob_get_clean();
$assert(!str_contains($nav, 'analysis.php') && !str_contains($nav, '>Trends<'), 'Product navigation no longer advertises Trends');
foreach (['/newsroom.php', '/search.php', '/news-trails.php', '/scroll-archive.php', '/timeline.php', '#browseNewsModal', '#analyzeModal'] as $destination) {
    $assert(str_contains($nav, $destination), 'Navigation preserves ' . $destination);
}
$assert(str_contains($nav, 'data-target="#snProductNavCollapse"'), 'Responsive navigation toggle remains wired');
$assert(!str_contains($modules, 'analysis.php') && !str_contains($modules, '>Trends<'), 'Homepage has no Trends card');
$assert(substr_count($modules, 'class="portfolio-item"') === 7, 'Seven remaining homepage modules');
$assert(substr_count($modules, 'col-lg-3 col-sm-6 mb-4') === 4 && substr_count($modules, 'col-lg-4 col-sm-6 mb-4') === 3, 'Desktop uses four then three balanced cards; smaller grids stay centered');

foreach ([
    'views/partials/___topnav_full.php', 'views/partials/___topnav_basic.php',
    '500.html', 'how-it-works.php', 'index.php',
    'core/render/___render.php', 'views/home/panels/___news_intel_panel.php',
    'views/home/panels/___story_threads.php', 'views/home/panels/___active_stories.php',
    'views/topics/___topic_article.php',
] as $file) {
    $assert(!str_contains(file_get_contents(BASE_PATH . '/' . $file), 'analysis.php'), 'No retired links in ' . $file);
}
$threads = file_get_contents(BASE_PATH . '/views/home/panels/___story_threads.php');
$assert(str_contains($threads, '<span class="sn-st-link is-quiet">'), 'Story Threads category is plain text');
$assert(!str_contains(file_get_contents(BASE_PATH . '/sitemap.xml'), 'analysis'), 'Sitemap has no Analysis URLs');
$assert(str_contains(file_get_contents(BASE_PATH . '/api/analyze.php'), 'azeo_site_results($url)'), 'Article analysis API retains its implementation');
$assert(str_contains(file_get_contents(BASE_PATH . '/.htaccess'), 'RewriteRule ^(.+?)/?$ $1.php [L]'), 'Existing clean-path routing still maps to the protected endpoint');

foreach ($failures as $failure) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
}
if ($failures) {
    exit(1);
}
if (($argv[1] ?? '') === '--preview') {
    $css = file_get_contents(BASE_PATH . '/assets/css/styles.css')
        . file_get_contents(BASE_PATH . '/assets/css/product-nav.css');
    $html = preg_replace('~<script\b[^>]*>.*?</script>~si', '', $nav . $modules);
    $html = preg_replace('~<link\b[^>]*>|<img\b[^>]*>~i', '', $html);
    echo '<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>'
        . $css . '</style></head><body>' . $html . '</body></html>';
} else {
    echo 'PASS: ' . $checks . ' offline Analysis retirement checks' . PHP_EOL;
}
