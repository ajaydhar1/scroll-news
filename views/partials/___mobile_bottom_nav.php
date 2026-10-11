<?php
$snMobileNavPath = parse_url($_SERVER['SCRIPT_NAME'] ?? '/', PHP_URL_PATH) ?: '/';
$snMobileNavHome = in_array($snMobileNavPath, ['/', '/index.php'], true);
$snMobileNavWatch = $snMobileNavPath === '/watch.php';
$snMobileNavTrails = in_array($snMobileNavPath, ['/news-trails.php', '/news-trail-player.php'], true);
$snMobileNavAccount = strpos($snMobileNavPath, '/account/') === 0;
?>
<link href="/assets/css/components/mobile-bottom-nav.css?v=<?= filemtime(BASE_PATH . '/assets/css/components/mobile-bottom-nav.css') ?>" rel="stylesheet" />

<nav class="sn-mobile-bottom-nav" aria-label="Mobile navigation" hidden>
    <a class="sn-mobile-bottom-nav__link" href="/"<?= $snMobileNavHome ? ' aria-current="page"' : '' ?>>
        <i class="fas fa-home" aria-hidden="true"></i>
        <span>Home</span>
    </a>
    <a class="sn-mobile-bottom-nav__link" href="/watch.php"<?= $snMobileNavWatch ? ' aria-current="page"' : '' ?>>
        <i class="fas fa-tv" aria-hidden="true"></i>
        <span>Watch</span>
    </a>
    <a class="sn-mobile-bottom-nav__link sn-mobile-bottom-nav__link--stumble" href="/newsroom.php" aria-label="Stumble to a random article">
        <span class="sn-mobile-bottom-nav__stumble-icon" aria-hidden="true"><i class="fas fa-random"></i></span>
        <span>Stumble</span>
    </a>
    <a class="sn-mobile-bottom-nav__link" href="/news-trails.php"<?= $snMobileNavTrails ? ' aria-current="page"' : '' ?>>
        <i class="fas fa-compass" aria-hidden="true"></i>
        <span>Trails</span>
    </a>
    <a class="sn-mobile-bottom-nav__link" href="/account/"<?= $snMobileNavAccount ? ' aria-current="page"' : '' ?>>
        <i class="fas fa-user" aria-hidden="true"></i>
        <span>Account</span>
    </a>
</nav>

<script src="/assets/js/mobile-bottom-nav.js?v=<?= filemtime(BASE_PATH . '/assets/js/mobile-bottom-nav.js') ?>" defer></script>