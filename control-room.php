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
        
            <video autoplay muted loop playsinline id="myVideo">
                <source src="assets/videos/newsroom.mp4" type="video/mp4">
            </video>

            <!-- Top nav-->        
            <?php require_once __DIR__ . '/___topnav_full.php'; ?>

            <div class="content">
                <h1 class="text-white">Stumble Through the News</h1>
                <h2 class="brand-text">Smart analytics. Fresh perspectives.</h2>
                <!-- Use a button to pause/play the video with JavaScript -->
                <!--<button type="submit" class="btn btn-lg text-white font-weight-bold" style="background-color: black;" id="myBtn" onclick="goToAnalytics()">Tell Me More</button>-->
            
                <!-- Footer-->        
                <?php require_once __DIR__ . '/___footer_control_room.php'; ?>

            </div>
        </div>

        <!-- Modals-->        
        <?php require_once __DIR__ . '/___modals.php'; ?>

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
