<?php

http_response_code(410);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page retired | Scroll News</title>
    <style>
        body { margin: 0; padding: 2rem 1rem; font: 1rem/1.6 system-ui, sans-serif; color: #17322a; background: #f5f7f6; }
        main { max-width: 38rem; margin: 8vh auto; }
        a { color: #126044; }
        nav { display: flex; flex-wrap: wrap; gap: 1rem; }
    </style>
</head>
<body>
    <main>
        <h1>This page has been retired</h1>
        <p>The legacy Analysis / Trends page is no longer available. You can still search headlines, explore the Newsroom, and analyze individual articles.</p>
        <nav aria-label="Continue exploring">
            <a href="/search.php">Search headlines</a>
            <a href="/newsroom.php">Open Newsroom</a>
            <a href="/">Scroll News home</a>
        </nav>
    </main>
</body>
</html>
