<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Use the Scroll News Control Room to fine-tune how you browse U.S. news, adjust filters, and explore tools for analyzing headlines, entities, and narrative trends." />
        <meta name="author" content="Scroll News" />
        <title>Scroll News Control Room – Tune Your Newsroom Tools</title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="assets/img/play-green.png" />
        <link rel="canonical" href="https://scrollnews.io/control-room">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.io/control-room.php" />
        <meta property="og:title" content="Scroll News Control Room — Tools & analysis" />
        <meta property="og:description" content="Access the Scroll News control room to experiment with article analysis, entity extraction, and other news intelligence tools." />
        <meta property="og:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-control-room-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.io/control-room.php" />
        <meta name="twitter:title" content="Scroll News Control Room — Tools & analysis" />
        <meta name="twitter:description" content="Access the Scroll News control room to experiment with article analysis, entity extraction, and other news intelligence tools." />
        <meta name="twitter:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-control-room-1200x630.png" />

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

        <style>

            :root {
                --link-color: #00bfa6; /* Light Gray */
                --light-text: gray;
            }

            .content {
                position: relative;
                color: #0a0a0a;
                width: 100%;
                text-align: center;

                /* Make this section fill the screen and sit above the footer */
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: flex-end; /* pushes heading/footer toward bottom */

                /* Leave room for the sticky nav at the top */
                padding-top: 5rem; /* tweak if your nav is taller/shorter */
            }

            /* Give a little extra space on small screens where the nav wraps to multiple rows */
            @media (max-width: 768px) {
                .content {
                    padding-top: 6.5rem;
                }
            }

            a {
                color: var(--link-color);
            }

            .light-text {
                color: var(--light-text);
            }

            .brand-text {
                color: var(--brand-color);
            }

            html, body {
              height: 100%;
              margin: 0;
              padding: 0;
            }

            .page-container {
              min-height: 100vh; /* full viewport height */
              display: flex;
              flex-direction: column;
            }

            .footer-bottom {
              margin-top: auto; /* pushes footer down neatly */
            }

            h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
                line-height: calc(var(--line-height-unit) * 1.1) !important;
            }

        </style>


    </head>

    <body id="page-top">

        <div class="page-container">

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
        

            <video autoplay muted loop playsinline id="myVideo">
                <source src="assets/videos/newsroom.mp4" type="video/mp4">
            </video>

            <!-- topnav-->
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
                                <a class="search-button mr-2" href="analysis.php?context=category&value=politics&w=7d" title="Analyze trends" aria-label="Analyze trends" data-loading>📊</a>
                                <a class="search-button" href="search.php" title="Search" aria-label="Search">🔍</a>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>

            <div class="content">
                <h1 class="text-white">Stumble Through the News</h1>
                <h2 class="brand-text">Smart analytics. Fresh perspectives.</h2>
                <!-- Use a button to pause/play the video with JavaScript -->
                <!--<button type="submit" class="btn btn-lg text-white font-weight-bold" style="background-color: black;" id="myBtn" onclick="goToAnalytics()">Tell Me More</button>-->
            
                <!-- Footer-->        
                <div class="bg-dark mt-4 control-footer-wrap">      
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

            </div>


        </div>


        <!-- Bootstrap core JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.0/jquery.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
        <!-- Third party plugin JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
        <!-- Contact form JS-->
        <script src="assets/mail/jqBootstrapValidation.js"></script>
        <script src="assets/mail/contact_me.js"></script>
        <!-- Core theme JS-->
        <!--<script src="js/scripts.js"></script>-->

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
