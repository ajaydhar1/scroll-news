<?php

if (($_GET['context'] ?? '') === 'trail-player') {
    return;
}

require_once __DIR__ . '/../../auth/includes/auth_bootstrap.php';

?>

<link href="/assets/css/product-nav.css?v=<?= filemtime(BASE_PATH . '/assets/css/product-nav.css'); ?>" rel="stylesheet" />

<div id="snProductLoadingOverlay" class="loading-overlay" aria-live="polite" aria-busy="true" hidden>
    <div class="loading-spinner" role="status" aria-label="Loading"></div>
</div>

<header class="sn-product-nav-shell sticky-top">
    <nav class="sn-product-nav container-fluid" aria-label="Main navigation">
        <a class="sn-product-brand" href="/" data-loading>
            <img src="/assets/img/play-green.png" alt="" />
            <span>Scroll News</span>
        </a>

        <button class="sn-product-toggler" type="button" data-toggle="collapse" data-target="#snProductNavCollapse" aria-controls="snProductNavCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>

        <div class="collapse sn-product-collapse" id="snProductNavCollapse">
            <ul class="sn-product-links">
                <li class="sn-product-item sn-product-stumble-item">
                    <a data-step="1" data-intro="Welcome to the Scroll News newsroom! Here we provide analytics for the latest news stories. Click this play button to stumble through trending articles." class="sn-product-link sn-product-stumble" href="/newsroom.php" onclick="trackStumbleClick('top_nav_product')" data-loading>
                        <i class="fas fa-play" aria-hidden="true"></i>
                        <span>Stumble</span>
                    </a>
                </li>

                <li class="sn-product-item dropdown">
                    <button class="sn-product-link dropdown-toggle" id="snProductBrowseDropdown" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Browse
                    </button>
                    <div class="dropdown-menu" aria-labelledby="snProductBrowseDropdown">
                        <button class="dropdown-item" type="button" data-toggle="modal" data-target="#browseNewsModal" aria-label="Browse news by topic">Browse by Topic</button>
                        <a class="dropdown-item" href="/scroll-archive.php" data-loading>Scroll Archive</a>
                    </div>
                </li>

                <li class="sn-product-item">
                    <a class="sn-product-link" href="/search.php" aria-label="Search headlines">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>Search</span>
                    </a>
                </li>

                <li class="sn-product-item dropdown">
                    <button class="sn-product-link dropdown-toggle" id="snProductAnalyzeDropdown" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Analyze
                    </button>
                    <div class="dropdown-menu" aria-labelledby="snProductAnalyzeDropdown">
                        <button class="dropdown-item" type="button" data-toggle="modal" data-target="#analyzeModal" aria-label="Analyze an article by URL">Analyze Article</button>
                        <a class="dropdown-item" href="/analysis.php?context=category&amp;value=politics&amp;w=7d" data-loading>Trends</a>
                    </div>
                </li>

                <li class="sn-product-item">
                    <a class="sn-product-link" href="/news-trails.php" title="News Trails" aria-label="News Trails" data-loading>
                        <span aria-hidden="true">🧭</span>
                        <span>News Trails</span>
                    </a>
                </li>

                <li class="sn-product-item">
                    <a class="sn-product-link" href="/control-room.php" title="Control Room" aria-label="Control Room">
                        <i class="fas fa-sliders-h" aria-hidden="true"></i>
                        <span>Control Room</span>
                    </a>
                </li>

                <li class="sn-product-item sn-product-theme-item">
                    <button class="sn-product-link sn-product-theme" id="themeToggle" type="button" aria-label="Switch to Dark City theme" title="Switch to Dark City theme" aria-pressed="false">
                        <span class="sn-product-theme-icon" aria-hidden="true"><i class="fas fa-moon"></i></span>
                        <span>Theme</span>
                    </button>
                </li>
            </ul>

            <div class="sn-product-account">
                <?php if (!empty($currentUser)): ?>
                    <?php require BASE_PATH . '/views/partials/___account_menu.php'; ?>
                <?php else: ?>
                    <a class="sn-product-sign-in" href="/auth/login.php">Sign in</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<script>
    window.pubsToFilterOut = <?= json_encode($filter_out ?? []); ?>;

    function trackStumbleClick(location = 'unknown') {
        if (typeof gtag === 'function') {
            gtag('event', 'stumble_click', {
                event_category: 'engagement',
                event_label: location,
                page_location: window.location.href,
                transport_type: 'beacon'
            });
        }
    }

    (function() {
        const overlay = document.getElementById('snProductLoadingOverlay');
        const show = () => overlay && (overlay.hidden = false);
        const hide = () => overlay && (overlay.hidden = true);
        const themeIcon = document.querySelector('.sn-product-theme-icon');

        function syncThemeIcon() {
            if (!themeIcon) return;

            const icon = document.createElement('i');
            icon.className = document.documentElement.dataset.uiTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            themeIcon.replaceChildren(icon);

            if (window.FontAwesome && window.FontAwesome.dom) {
                window.FontAwesome.dom.i2svg({ node: themeIcon });
            }
        }

        window.addEventListener('pageshow', hide);
        document.addEventListener('click', function(event) {
            const el = event.target.closest('[data-loading]');
            if (!el || event.defaultPrevented) return;
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

            const link = el.closest('a');
            if (link) {
                const target = link.getAttribute('target');
                if (target && target !== '_self') return;
                if (link.hasAttribute('download')) return;
                const href = link.getAttribute('href') || '';
                // In-page anchors don't unload the page, so the overlay would never clear.
                if (href === '' || href.charAt(0) === '#') return;
            }
            show();
        });

        syncThemeIcon();
        new MutationObserver(syncThemeIcon).observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-ui-theme']
        });
    })();
</script>

<script src="/assets/js/newsroom/utils.js" defer></script>
<script src="/assets/js/newsroom/modules.js" defer></script>
