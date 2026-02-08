<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Review the Terms & Conditions for using Scroll News, including guidelines, responsibilities, and important legal information." />
        <meta name="author" content="Scroll News" />
        <title>Terms & Conditions | Scroll News</title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="assets/img/play-green.png" />
        <link rel="canonical" href="https://scrollnews.io/terms">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.io/terms.php" />
        <meta property="og:title" content="Scroll News Terms of Service" />
        <meta property="og:description" content="Read the Terms of Service for using Scroll News, including acceptable use, limitations of liability, and other legal details." />
        <meta property="og:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-terms-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.io/terms.php" />
        <meta name="twitter:title" content="Scroll News Terms of Service" />
        <meta name="twitter:description" content="Read the Terms of Service for using Scroll News, including acceptable use, limitations of liability, and other legal details." />
        <meta name="twitter:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-terms-1200x630.png" />

        <!-- Font Awesome icons (free version)-->
        <script src="https://use.fontawesome.com/releases/v6.7.2/js/all.js" crossorigin="anonymous"></script>

        <!-- Google fonts-->
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css?family=Droid+Serif:400,700,400italic,700italic" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700" rel="stylesheet" type="text/css" />
        

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Lato&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600&family=Inter&display=swap" rel="stylesheet">

        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>" rel="stylesheet" />
        <link href="css/custom.css?v=<?php echo filemtime(__DIR__ . '/css/custom.css'); ?>" rel="stylesheet" />

        <script src="https://www.amcharts.com/lib/3/amcharts.js"></script>
        <script src="https://www.amcharts.com/lib/3/serial.js"></script>
        <script src="https://www.amcharts.com/lib/3/plugins/export/export.min.js"></script>
        <link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all" />
        <script src="https://www.amcharts.com/lib/3/themes/light.js"></script>

        <!-- Add IntroJs styles -->
        <link href="css/introjs.css" rel="stylesheet">

        <link href="css/lightbox.css" rel="stylesheet" />

        <style>

            body#page-top {
                background: #fafafa;
            }

            .custom-highlight {
                background-color: transparent !important;
                opacity: 1 !important;
            }

            header.masthead {
                padding-top: 3.5rem;
                padding-bottom: 1.15rem;
            }

            @media (min-width: 768px) {
                header.masthead .masthead-heading {
                    font-size: 3.5rem;
                    font-weight: 700;
                    line-height: 1;
                    margin-bottom: 2rem;
                }
            }

            .btn-lg, .btn-group-lg > .btn {
                font-size: 1.2rem;
            }

            #myVideo {
                position: fixed;
                right: 0;
                top: 0;
                width: 100%;
                max-height: 100%;
            }

            .content {
                position: fixed;
                top: 50%;
                left: 0;
                background: rgba(255, 255, 255, 1);
                color: #0a0a0a;
                width: 100%;
                padding: 20px;
                height: 190px;
                margin-top: -95px;
                text-align: center;
            }

            .btn {
                box-shadow: 0 0 0 0.14rem var(--brand-color) !important;
            }

            .btn.btn-rectangle {
                box-shadow: 0 0 0 0.14rem var(--brand-color) !important;
            }

            .container.cover-img {      
                /* background-color: rgba(0, 247, 216, 0.8); */
                /* background-color: rgba(96, 125, 139, 0.8); */ /* #607D8B */
                background-color: rgb(20 20 20 / 80%);
                width: 100%;
                height: 100%;
            }

            a {
                color: var(--brand-color);
            }


            /*
            footer .text-lg-right a {
                color: #00bfa6;
            }

            footer .text-lg-right a:hover {
                color: black;
            }
            */

            .amcharts-export-menu.amcharts-export-menu-top-right.amExportButton {
                display: none;
            }

            .card-header {
                background-color: white;
            }

            .loader {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                background: url(assets/img/loading-2.gif) 50% 50% no-repeat #f9f9f9;
                opacity: 1;
            }

            #side-by-side-panel {
                margin-bottom: 40px;
            }

            ion-icon {
                font-size: 20px;
            }

            .card-body {
              max-height: 600px;
              overflow-y: auto;
            }

            .popover-header {
                display: none;
            }

            .popover-body {
                background: linear-gradient(to top right, #b91d73, #f953c6);
                color: white;
                border: solid 2px yellow;
                font-family: 'Open Sans', sans-serif;
            }

            .popover-body strong {
                color: yellow;
            }

            .popover-body img {
                object-fit: cover;
                object-position: center top;
                width: 100%;
                height: 200px;
                text-align: center;
                /* border: solid yellow 1px; */
            }

            button.nav-link.active {
                background: linear-gradient(to top right, #b91d73, #f953c6);
                color: white !important;
                /* border: solid 2px yellow !important; */
            }

            #wiki-list-1 img {
                width: 170px;
                height: 170px;
                object-fit: cover;
                margin: 5px;
            }

            .description {
                color: #525252;
                font-size: 16.69px;
            }

            .card.shadow {
                box-shadow: none !important;
            }

            #depth-panel {
                background-color: #fafafa;
            }

            .introjs-helperLayer {
                border: 1px solid rgb(255 255 255 / 87%) !important;
            }

            .introjs-overlay {
                opacity: .85 !important;
            }

            .introjs-tooltiptext {
                font-size: 14px;
            }

            .analyze-btn {
                box-shadow: 0 0 0 0.14rem var(--brand-color-break) !important;
            }

            .analyze-btn:hover {
                background: #00bfa6;
            }


            .browse-btn {
                color: var(--dark);
                box-shadow: 0 0 0 0.14rem var(--brand-color-break) !important;
            }

            .browse-btn:hover {
                color: white;
            }

            .analyze-btn,
            .browse-btn {
                width: 150px;
            }

            #browseNewsModal p.card-text {
                font-size: 14px;
            }

            .card-img-top.news-modal {
                height: 230px;
                object-fit: cover;
            }

            /*
            #browseNewsModal .modal-content {
              background: rgba(255, 255, 255, 0.75);
              backdrop-filter: blur(12px);
              -webkit-backdrop-filter: blur(12px);
              border-radius: 16px;
              box-shadow: 0 8px 32px rgba(0,0,0,0.2);
              border: 1px solid rgba(255,255,255,0.3);
            }
            */

            /*
            #browseNewsModal .modal-content {
              background-color: #1e1e1e;
              color: #f1f1f1;
            }

            #browseNewsModal .card {
              background-color: #2a2a2a;
              border: 1px solid #00bfa6;
            }
            */

            /*
            .modal-content {
              background-color: #fdfcf8;
              font-family: 'Georgia', serif;
            }

            .card {
              background-color: #ffffff;
              border: 1px solid #ddd;
              box-shadow: none;
            }
            */

            .nav-tabs .nav-link {
                border: 1px solid #cbcbcb;
                border-top-left-radius: 0.5rem;
                border-top-right-radius: 0.5rem;
                margin-top: -7px;
            }

            .nav-tabs .nav-link:hover, .nav-tabs .nav-link:focus {
                border-color: #cbcbcb;
            }

            .category-link:hover {
                color: var(--brand-hover-color);
            }

            .category-no-link {
                color: grey;
                text-decoration: none;
            }

            .category-no-link:hover {
                color: grey;
                text-decoration: none;
                cursor: text;
            }

            .form-control:focus {
                box-shadow: 0 0 0 0.2rem var(--brand-color);
            }

        </style>


    </head>
    <body id="page-top">

        <!-- Loading overlay -->
        <div id="loadingOverlay" class="loading-overlay" aria-live="polite" aria-busy="true" hidden>
          <div class="loading-spinner" role="status" aria-label="Loading"></div>
        </div>

        <style>
          .loading-overlay{
            position:fixed; inset:0; display:flex; align-items:center; justify-content:center;
            background:rgba(255,255,255,0.82); z-index:2000; backdrop-filter:saturate(120%) blur(2px);
          }
          .loading-spinner{
            width:48px; height:48px; border:4px solid #e5e7eb; border-top-color:#0d6efd;
            border-radius:50%; animation:spin 1s linear infinite;
          }
          @keyframes spin{to{transform:rotate(360deg)}}
          @media (prefers-reduced-motion: reduce){ .loading-spinner{animation:none} }
        </style>

        <!-- Legal topnav-->
        <footer class="footer py-4 bg-white sticky-top sn-top-nav">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 d-flex text-lg-left text-bolder">
                        <h5 class="mb-2 mb-sm-0">
                            <a href="index.php" data-loading>
                                <img src="assets/img/play-green.png" alt="Logo play button" style="height: 24px; width: auto; vertical-align: middle; margin-right: 5px; margin-bottom: 5px;">
                                Scroll News
                            </a>
                        </h5>
                    </div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <a class="btn btn-black btn-social mx-2" title="History" href="scroll-history.php" data-loading><i class="fas fa-history"></i></a>
                        <a class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" onclick="" data-loading><i class="fas fa-play"></i></a>
                        <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.php"><i class="fas fa-dashboard"></i></a>
                    </div>
                    <div class="col-lg-4 d-flex text-lg-right" style="">
                        <div class="ml-auto">
                            <a href="about.php" class="mr-3">About</a>
                            <a class="search-button" href="search.php" title="Search" aria-label="Search">🔍</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Masthead-->
        <header class="masthead" style="background-image: url();">
            <div class="container cover-img bg-dark py-5">
                <div class="mb-2 text-muted" style="font-size: 1.25rem;"><strong>#starter version</strong></div>
                <div class="masthead-heading text-uppercase">Terms & Conditions</div>
            </div>
        </header>

        
        <!-- Content area--> 
        <div class="container py-5">
            <p><strong>Last Updated:</strong> August 1, 2025</p>

            <p>Welcome to <strong>Scroll News</strong>. By accessing or using this website (scrollnews.io), you agree to be bound by these Terms and Conditions.</p>

            <h4>1. Use of Site</h4>
            <p>Scroll News provides a streamlined way to view curated news content. You agree not to use this site for unlawful purposes or interfere with its normal operation.</p>

            <h4>2. Intellectual Property</h4>
            <p>All content on this site, unless otherwise stated, is owned or licensed by Scroll News. Please don’t copy, reproduce, or redistribute content without permission.</p>

            <h4>3. Disclaimer</h4>
            <p>Scroll News presents content for informational purposes only. We do not guarantee the accuracy, timeliness, or completeness of any third-party content.</p>

            <h4>4. Changes</h4>
            <p>These terms may be updated occasionally. By continuing to use the site, you accept any changes.</p>

            <h4>5. Contact</h4>
            <p>Questions? Reach out to us at <a href="mailto:your@email.com">your@email.com</a>.</p>
        </div>

        <!-- Footer-->        
        <div class="bg-dark mt-5" style="height: 338px;">        
            <footer class="footer footer-bottom bg-white py-4">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-4 text-lg-left">Copyright © Scroll News 2026</div>
                        <div class="col-lg-4 my-3 my-lg-0">
                            <a class="btn btn-black btn-social mx-2" title="X profile" href="https://x.com/scrollnewsio" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="History" href="scroll-history.php" data-loading><i class="fas fa-history"></i></a>
                            <a class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" data-loading><i class="fas fa-play"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.php"><i class="fas fa-dashboard"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="IG profile" href="https://www.instagram.com/scrollnewsio/" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                        <div class="col-lg-4 text-lg-right font-weight-bold">
                            <a href="index.php" data-loading>scroll news</a>
                            <br>
                            <a href="about.php" class="text-muted small mr-3">About</a>
                            <a href="terms.php" class="text-muted small mr-3">Terms</a>
                            <a href="privacy.php" class="text-muted small">Privacy</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        

        <!-- Bootstrap core JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
        <!-- Third party plugin JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
        <!-- Contact form JS-->
        <script src="assets/mail/jqBootstrapValidation.js"></script>
        <script src="assets/mail/contact_me.js"></script>
        <!-- Core theme JS-->
        <!--<script src="js/scripts.js"></script>-->

        <script src="js/lottie.js" type="text/javascript"></script>

        <script type="text/javascript" src="js/intro.js"></script>

        <script type="text/javascript" src="js/lightbox.js"></script>

        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        
        <script src="https://unpkg.com/ionicons@5.2.3/dist/ionicons.js"></script>
          
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

    </body>
</html>
