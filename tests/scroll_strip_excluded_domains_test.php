<?php
// Minimal check for sn_is_excluded_domain(); extracts the helper to avoid loading the whole module.
$src = file_get_contents(__DIR__ . '/../core/___modules.php');
preg_match('/\$SCROLL_STRIP_EXCLUDED_DOMAINS = \[.*?\n\];\s*function sn_is_excluded_domain.*?\n}\n/s', $src, $m);
eval($m[0]);
$cases = [
    ['https://www.uco.es/noticia', true],
    ['https://UCO.ES/x', true],
    ['http://WWW.UCO.ES./x', true],
    ['https://sub.uco.es/x', true],
    ['https://notuco.es/x', false],
    ['https://example.com/www.uco.es', false],
    ['', false],
];
$fail = 0;
foreach ($cases as [$u, $e]) {
    if (sn_is_excluded_domain($u) !== $e) { echo "FAIL $u\n"; $fail++; }
}
echo $fail ? "FAILED\n" : "OK\n";
exit($fail ? 1 : 0);
