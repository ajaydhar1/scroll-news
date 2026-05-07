<!-- Loading overlay -->
<div id="loadingOverlay" class="loading-overlay" aria-live="polite" aria-busy="true" hidden>
    <div class="loading-spinner" role="status" aria-label="Loading"></div>
</div>

<footer class="footer py-4 bg-white sticky-top sn-top-nav">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-4 text-lg-left d-flex justify-content-between align-items-center">
                <h5 class="mb-2 mb-sm-0 align-items-center">
                    <a href="/" data-loading>
                        <img src="/assets/img/play-green.png" alt="Logo" style="height: 24px; width: auto; vertical-align: middle; margin-right: 5px; margin-bottom: 5px;">
                        Scroll News
                    </a>
                </h5>
                <button class="btn btn-outline-dark analyze-btn" data-toggle="modal" data-target="#analyzeModal" aria-label="Analyze an article by URL">
                    Analyze Article
                </button>
            </div>
            <div class="col-lg-4 my-3 my-lg-0">
                <a data-step="2" data-intro="Click here for a feed of fresh articles analyzed and indexed by Scroll News." class="btn btn-black btn-social mx-2" title="Scroll Archive" href="scroll-archive.php" data-loading><i class="fas fa-history"></i></a>
                <a data-step="1" data-intro="Welcome to the Scroll News newsroom! Here we provide analytics for the latest news stories. Click this play button to stumble through trending articles." class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" onclick="trackStumbleClick('top_nav')" data-loading><i class="fas fa-play"></i></a>
                <a data-step="3" data-intro="Click here to see our control room — a personalized intelligence layer that visualizes your reading patterns, engagement signals, and news behavior over time." class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.php"><i class="fas fa-dashboard"></i></a>
            </div>
            <div class="col-lg-4 text-lg-right d-flex justify-content-between" style="">
                <button class="btn btn-outline-dark blue-hover browse-btn mr-3" data-toggle="modal" data-target="#browseNewsModal" aria-label="Browse news by topic">
                    Browse News
                </button>
                
                <?php /*
                <button class="btn btn-outline-dark blue-hover browse-btn ml-2" data-toggle="modal" data-target="#searchNewsModal" aria-label="Search news articles">
                    Search News
                </button>
                */ ?>
                <div style="margin-top: 3px;">
                    <?php //<a href="about.php" class="mr-3">About</a> ?>
                    <a class="search-button mr-2" href="analysis.php?context=category&value=politics&w=7d" title="Analyze trends" aria-label="Analyze trends" data-loading>📊</a>
                    <a class="search-button mr-2" 
                        href="how-it-works.php" 
                        title="How this works" 
                        aria-label="How this works">
                        ?
                    </a>
                    <a class="search-button" href="search.php" title="Search" aria-label="Search">🔍</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
    pubsToFilterOut = <?php echo json_encode($filter_out ?? []); ?>;
</script>

<script src="/assets/js/newsroom/utils.js" defer></script>
<script src="/assets/js/newsroom/modules.js" defer></script>

<script>
    (function(){
        const overlay = document.getElementById('loadingOverlay');
        const show = () => overlay && (overlay.hidden = false);
        const hide = () => overlay && (overlay.hidden = true);

        // Show spinner when navigating away (page links/forms)
        //window.addEventListener('beforeunload', show);

        // Hide when page is ready (covers BFCache too)
        window.addEventListener('pageshow', hide);

        // For specific buttons/links, add data-loading attribute
        document.addEventListener('click', function(e){
        const t = e.target.closest('[data-loading]');
        if (t) show();
        });

        // Optional: inline button spinner (keeps overlay too)
        document.addEventListener('click', function(e){
        const btn = e.target.closest('[data-loading-btn]');
        if (!btn) return;
        btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span>&nbsp;Loading…';
        btn.classList.add('disabled'); btn.setAttribute('aria-busy','true');
        });

        // Minimal CSS for inline button spinner:
        const style = document.createElement('style');
        style.textContent = '.btn-spinner{display:inline-block;width:1em;height:1em;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:spin .6s linear infinite;vertical-align:-0.125em}';
        document.head.appendChild(style);
    })();
</script>

<script>

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

</script>