<?php
error_reporting(E_ERROR | E_PARSE);
require_once('___session_results.php');
require_once('___modules.php');

require_once __DIR__ . '/newsroom_core/___request_resolution_layer.php';
require_once __DIR__ . '/newsroom_core/___newsroom_meta.php';

// Resolve chosen article (redirects if none)
$resolved = newsroom_resolve_article();
$url      = $resolved['url'];
$category = $resolved['category'] ?? '';
$db = $resolved['db'] ?? '';

$fromDb = $db == "1" || $category == "db";

// Get meta data for article
$meta   = [];
$title  = $des = $img = $pub = $pub_link = $youtube_search = '';

// If we're in "db mode", try to pull the article from the DB instead of scraping
if ($db !== '') {
    $article = getArticleFromDBByUrl($url);  // your helper
    $meta = build_meta_from_db_article($url, $article);

    if ($meta['image'] == '') {
      $meta = newsroom_extract_meta($url);
    }
}
else {
    // Extract OpenGraph/meta
    $meta = newsroom_extract_meta($url);
}

// If you want individual vars (keeps the rest of your page unchanged):
$title           = $meta['title'];
$des             = $meta['description'];
$img             = $meta['image'];
$pub             = $meta['publisher'];
$pub_link        = $meta['publisher_link'];
$youtube_search  = $meta['youtube_search'];


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="robots" content="noindex, nofollow">
        <meta name="description" content="Browse the Scroll News Newsroom to see AI-analyzed U.S. headlines, scroll through article screenshots, and quickly understand what’s happening right now." />
        <meta name="author" content="Scroll News" />

        <title><?= clean_headline($title) ?></title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="assets/img/play-green.png" />

        <!-- Twitter card and Open Graph-->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Scroll News: [<?php echo $pub; ?> - <?= clean_headline($title) ?>]" />
        <meta name="twitter:description" content="<?= clean_headline($title) ?>" />
        <meta name="twitter:image" content="<?php echo $img; ?>" />
    
        <meta property="og:url" content="https://scrollnews.io" />
        <meta property="og:title" content="Scroll News: [<?php echo $pub; ?> - <?= clean_headline($title) ?>]" />
        <meta property="og:description" content="<?= clean_headline($title) ?>" />
        <meta property="og:image" content="<?php echo $img; ?>" />    
        <meta property="og:site_name" content="Scroll News" />

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
        <link href="css/styles.css" rel="stylesheet" />
        <link href="css/custom.css" rel="stylesheet" />
        <link href="css/newsroom.css" rel="stylesheet" />

        <script src="https://www.amcharts.com/lib/3/amcharts.js"></script>
        <script src="https://www.amcharts.com/lib/3/serial.js"></script>
        <script src="https://www.amcharts.com/lib/3/plugins/export/export.min.js"></script>
        <link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all" />
        <script src="https://www.amcharts.com/lib/3/themes/light.js"></script>

        <!-- Add IntroJs styles -->
        <link href="css/introjs.css" rel="stylesheet">
        <?php //<link href="https://unpkg.com/intro.js/minified/introjs.min.css" rel="stylesheet"> ?>

        <link href="css/lightbox.css" rel="stylesheet" />

        <script>
            // Flip this to false to go back to your old 2-AJAX flow instantly.
            const USE_UNIFIED_NEWSROOM_API = true;
        </script>

        <style>

            

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

        <!-- Footer-->
        
        <footer class="footer py-4 bg-white sticky-top sn-top-nav">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 text-lg-left d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 align-items-center">
                            <a href="index.php">
                                <img src="assets/img/play-green.png" alt="Logo" style="height: 24px; width: auto; vertical-align: middle; margin-right: 5px; margin-bottom: 5px;">
                                Scroll News
                            </a>
                        </h5>
                        <button class="btn btn-outline-dark analyze-btn" data-toggle="modal" data-target="#analyzeModal" aria-label="Analyze an article by URL">
                            Analyze Article
                        </button>
                    </div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <a data-step="2" data-intro="Click here for a feed of fresh articles analyzed and indexed by Scroll News." class="btn btn-black btn-social mx-2" title="History" href="scroll-history.php"><i class="fas fa-history"></i></a>
                        <a data-step="1" data-intro="Welcome to the Scroll News newsroom! Here we provide analytics for the latest news stories. Click this play button to stumble through trending articles." class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" onclick="" data-loading><i class="fas fa-play"></i></a>
                        <a data-step="3" data-intro="Click here to see our newsroom video trailer." class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.html"><i class="fas fa-align-left"></i></a>
                    </div>
                    <div class="col-lg-4 text-lg-right font-weight-bold" style="">
                        <button class="btn btn-outline-dark blue-hover browse-btn" data-toggle="modal" data-target="#browseNewsModal" aria-label="Browse news by topic">
                            Browse News
                        </button>
                        <button class="btn btn-outline-dark blue-hover browse-btn ml-2" data-toggle="modal" data-target="#searchNewsModal" aria-label="Search news articles">
                            Search News
                        </button>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Masthead-->
        <header class="masthead" style="background-image: url(<?php echo $img; ?>)">
            <div class="container cover-img py-5">
                <?php if (array_key_exists($_GET['category'], $rss_feeds)): ?>
                    <div class="mb-2" style="font-size: 1.25rem;"><strong><a href="" class="category-link" data-category="<?= $_GET['category'] ?>" data-category-url="<?= $rss_feeds[$_GET['category']] ?>">#<?= $_GET['category'] ?></a></strong></div>
                <?php endif; ?>
                <div class="masthead-subheading mb-1"><a href="<?= $pub_link ?>" target="_blank" class="bright-link-hover"><?php echo $pub; ?></a></div>
                <div class="mb-2 text-muted" style="font-size: 1.25rem;"><strong><?php if (isset($_GET['pub_date'])) { echo format_news_date($_GET["pub_date"]); } ?></strong></div>
                <div class="masthead-heading text-uppercase"><?php echo clean_headline($title); ?></div>
                <div class="text-center">
                    <a id="scroll" class="btn btn-green btn-lg btn-rectangle js-scroll-trigger text-black d-block d-md-inline-block btn-width-mobile-75 w-md-auto mx-auto mb-3 mr-md-2" href="#">Analytics</a>
                    <a class="btn btn-outline-secondary btn-lg btn-rectangle js-scroll-trigger d-block d-md-inline-block btn-width-mobile-75 w-md-auto mx-auto mb-3" target='_blank' href="<?php echo $url; ?>" style="color: white; border-color: transparent;">Go to Story</a>
                </div>
            </div>
        </header>

        <div class="container-fluid">
            <span class="link"><?= $url ?></span>
        </div>

        <a name="analytics"></a>

        <?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
            <div class="container-fluid mt-4">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                  <strong>Oops!</strong> We were unable to fully analyze this article. The NLP dashboard or image may be incomplete.
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
                </div>
            </div>
        <?php endif; ?>

        <div id="side-by-side-panel"> <?php // class="mb-4" style="min-height:480px;" ?>
            <div class="container-fluid" style="padding-top: 30px;">
                <div id="panel-inner-row" class="row flex-row"> <?php //  style="height: 95vh;" ?>
                    <!-- NLP Dashboard Panel -->
                    <div class="col-xxl-6 col-xl-12 col-lg-12 col-md-12 panel" style="overflow-y: auto; border-right: 2px solid #eee;">
                        <div class="text-center mb-3">
                            <h2>🧠 NLP Dashboard</h2>
                        </div>
                        <div id="analytics" class="skeleton">

                            <?php

                            if ($fromDb) {
                                $arr = $article['nlp'];

                                if (!$arr || (!empty($arr['error']) && $arr['error'] === 'No features in text.') || empty($arr['entities'])) {
                                    $host = parse_url($url, PHP_URL_HOST) ?: 'this page';
                                      // Small, in-panel empty state card
                                    echo '
                                    <div class="card shadow-sm border-0 empty-analytics">
                                      <div class="card-body d-flex align-items-start gap-3">
                                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                          <circle cx="12" cy="12" r="10" fill="#eef2ff"></circle>
                                          <path d="M12 7v6" stroke="#6366f1" stroke-width="2" stroke-linecap="round"/>
                                          <circle cx="12" cy="16" r="1.5" fill="#6366f1"/>
                                        </svg>
                                        <div>
                                          <h6 class="mb-1">Nothing to analyze</h6>
                                          <p class="mb-2 text-muted small">
                                            We couldn’t find enough readable text on <span class="fw-semibold">'.$host.'</span> to compute keywords, entities, topics, or sentiment.
                                          </p>
                                          <div class="d-flex gap-2">
                                            <a class="btn btn-sm btn-outline-secondary mr-2" href="'.$url.'" target="_blank" rel="noopener">Open article</a>';

                                        //<button class="btn btn-sm btn-primary" onclick="reanalyzeAnalytics('{$url}')">Retry</button>
                                    
                                    echo '
                                          </div>
                                          <details class="mt-2 small text-muted">
                                            <summary class="pointer">Why?</summary>
                                            <ul class="mb-0 ps-3">
                                              <li>Video/live page or gallery</li>
                                              <li>Very short post or headline-only</li>
                                              <li>Paywall or script-rendered content</li>
                                            </ul>
                                          </details>
                                        </div>
                                      </div>
                                    </div>
                                    ';
                                }

                                else {
                                    require_once("___nlp_body.php");
                                }
                            }

                            else {

                                echo '
                                    <div id="lottie" class="mb-4"></div>
                                    <!-- NLP results (injected from AJAX) will appear here -->
                                ';
                            
                            }

                            ?>
                        </div>
                    </div>

                    <!-- Article Screenshot Panel -->
                    <div class="col-xxl-6 col-xl-12 col-lg-12 col-md-12 panel" style="height: 100%; padding: 0; overflow-y: auto;"> <?php //background-color: #fcfcfc; ?>
                        <div class="text-center mb-3">
                            <h2>📰 Article Screenshot</h2>
                        </div>
                        <div class="d-flex justify-content-center align-items-start px-3">
                    
                            <?php 
                                if (!isset($_GET["error"])) {

                                    // 1) Prefer DB media image, then OG image ($img), else null
                                    $dbMedia   = $article['media_url'] ?? null;   // from DB
                                    $ogImage   = $img ?? null;           // scraped OG tag
                                    $primary   = $dbMedia ?: $ogImage;

                                    // 2) Hard fallback path
                                    $fallbackSrc = 'assets/img/news-placeholder.jpg';

                                    // 3) If we don't even have a primary, start with the fallback
                                    $initialSrc = $primary ?: $fallbackSrc;
                            ?>

                                    <img
                                      id="shot"
                                      src="<?php echo htmlspecialchars($initialSrc, ENT_QUOTES); ?>"
                                      alt="Article image"
                                      loading="lazy" decoding="async"
                                      style="width:100%;height:auto;margin-bottom:20px;"
                                    />

                                    <div id="img-loader" class="text-center mt-3">Loading image...</div>

                                    <script>
                                    (function () {
                                      const img        = document.getElementById('shot');
                                      const imgLoader  = document.getElementById('img-loader');
                                      const originalSrc = img.getAttribute('src');
                                      const fallbackSrc = '<?php echo $fallbackSrc; ?>';

                                      const hideLoader = () => { if (imgLoader) imgLoader.style.display = 'none'; };

                                      // If the image was cached and already loaded
                                      if (img.complete && img.naturalWidth > 0) {
                                        hideLoader();
                                      }

                                      img.addEventListener('load', () => {
                                        hideLoader();
                                      });

                                      img.addEventListener('error', () => {
                                        hideLoader();
                                        if (img.src !== fallbackSrc) {
                                          img.src = fallbackSrc;
                                        }
                                      });
                                    })();
                                    </script>

                            <?php
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer-->
        <div class="bg-dark" style="height: 338px;">        
            <footer class="footer footer-bottom bg-white pb-4">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-4 text-lg-left">Copyright © Scroll News 2025</div>
                        <div class="col-lg-4 my-3 my-lg-0">
                            <a class="btn btn-black btn-social mx-2" title="X profile" href="https://x.com/scrollnewsio" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="History" href="scroll-history.php"><i class="fas fa-history"></i></a>
                            <a class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" data-loading><i class="fas fa-play"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.html"><i class="fas fa-dashboard"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="IG profile" href="https://www.instagram.com/scrollnewsio/" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                        <div class="col-lg-4 text-lg-right font-weight-bold">
                            <a href="index.php">scroll news</a>
                            <br>
                            <a href="about.html" class="text-muted small mr-3">About</a>
                            <a href="terms.html" class="text-muted small mr-3">Terms</a>
                            <a href="privacy.html" class="text-muted small">Privacy</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>


        <?php require_once("views/modals/___modal_analyze.php"); ?>
        <?php require_once("views/modals/___modal_browse.php"); ?>
        <?php require_once("views/modals/___modal_search.php"); ?>


        

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
        <?php //<script type="text/javascript" src="https://unpkg.com/intro.js/minified/intro.min.js"></script> ?>

        <script type="text/javascript" src="js/lightbox.js"></script>

        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
          
        <script src="js/newsroom/utils.js" defer></script>
        <script src="js/newsroom/handlers.js" defer></script>
        <script src="js/newsroom/modules.js" defer></script>
        <!--<script src="js/newsroom/api_legacy.js" defer></script>-->
        <!--<script src="js/newsroom/api_unified.js" defer></script>-->
        <script src="js/newsroom/init.js" defer></script>

        <script>

            $(document).ready(function() {

                if ((<?=$_SESSION["resultViewed"]?> < 2) && ('<?=$_GET['siteSubmit']?>' != 'true')) {
                    introJs().setOptions({
                        highlightClass: 'custom-highlight',
                        overlayOpacity: 0.5  // or 0 if you want no darkening at all
                    }).start();
                }

            });

        </script>

        <script>

          pubsToFilterOut = <?php echo json_encode($filter_out); ?>;

        </script>

        <script>

          function setupAnalyticsJS() {

              $("#insta-link").click(function() {
                  $("#sm-tags").text("Instagram");
                  $("#twitter-tags").css("display", "none");
                  $("#insta-tags").css("display", "block");
                  $("#google-tags").css("display", "none");
                  $("#youtube-tags").css("display", "none");
                  $("#idea-tags").css("display", "none");
                  $("#twitter-icon").css("color", "#34495E");
                  $("#insta-icon").css("color", "var(--brand-color)");
                  $("#google-icon").css("color", "#34495E");
                  $("#youtube-icon").css("color", "#34495E");
                  $("#idea-icon").css("color", "#34495E");  
              });

              $("#twitter-link").click(function() {
                  $("#sm-tags").text("Twitter");
                  $("#insta-tags").css("display", "none");
                  $("#twitter-tags").css("display", "block");
                  $("#google-tags").css("display", "none");
                  $("#youtube-tags").css("display", "none");
                  $("#idea-tags").css("display", "none");
                  $("#insta-icon").css("color", "#34495E");
                  $("#twitter-icon").css("color", "var(--brand-color)");
                  $("#google-icon").css("color", "#34495E");
                  $("#youtube-icon").css("color", "#34495E");
                  $("#idea-icon").css("color", "#34495E");
              });

              $("#google-link").click(function() {
                  $("#sm-tags").text("Google");
                  $("#insta-tags").css("display", "none");
                  $("#twitter-tags").css("display", "none");
                  $("#google-tags").css("display", "block");
                  $("#youtube-tags").css("display", "none");
                  $("#idea-tags").css("display", "none");
                  $("#insta-icon").css("color", "#34495E");
                  $("#twitter-icon").css("color", "#34495E");
                  $("#google-icon").css("color", "var(--brand-color)");
                  $("#youtube-icon").css("color", "#34495E");
                  $("#idea-icon").css("color", "#34495E");
              });

              $("#youtube-link").click(function() {
                  $("#sm-tags").text("Youtube");
                  $("#insta-tags").css("display", "none");
                  $("#twitter-tags").css("display", "none");
                  $("#google-tags").css("display", "none");
                  $("#idea-tags").css("display", "none");
                  $("#youtube-tags").css("display", "block");
                  $("#insta-icon").css("color", "#34495E");
                  $("#twitter-icon").css("color", "#34495E");
                  $("#google-icon").css("color", "#34495E");
                  $("#youtube-icon").css("color", "var(--brand-color)");
                  $("#idea-icon").css("color", "#34495E");
              });

              /*
              $("a#gg").fancybox({
                  type: 'iframe',
                  fitToView: false,
                  width: '90%',
                  height: '90%',
                  autoSize: false,
                  closeClick: true,
                  openEffect: 'none',
                  closeEffect: 'none'

              });
              */

              // $.getScript('js/functions.js');


              // Setup ___get_definitions_js.php

              var tapped = 0;

              $(function() {

                  $('body *').not("#wikipedia a").click(function() {
                      $("#wikipedia a").popover('hide');
                      tapped = 0;
                  });

                  $('body *').not(".hashtag a").click(function() {
                      $(".hashtag a").popover('hide');
                      tapped = 0;
                  });



                  $("#wikipedia a").each(function(i, obj) {
                      $(this).attr("data-container", "body");
                      $(this).attr("data-toggle", "popover");
                      $(this).attr("data-placement", "bottom");
                      $(this).attr("data-content", "temp");
                      $(this).attr("data-html", "true");
                  })

                  $(".hashtag a").each(function(i, obj) {
                      $(this).attr("data-container", "body");
                      $(this).attr("data-toggle", "popover");
                      $(this).attr("data-placement", "auto");
                      $(this).attr("data-content", "temp");
                      $(this).attr("data-html", "true");
                  })




                  if (!isMobile()) {
                      $("#wikipedia a").mouseenter(function() {
                          getDefinitions($(this), $(this).text());
                      }).mouseleave(function() {
                          $(this).popover('hide');
                      });

                      $(".hashtag a").mouseenter(function() {
                          getDefinitions($(this), $(this).attr("data-hashtext"));
                      }).mouseleave(function() {
                          $(this).popover('hide');
                      });
                  }
                  else {
                      $("#wikipedia a").on("click", function(e) {
                          // open popover
                          if (tapped == 0) { // if no popover open
                              // show popover
                              getDefinitions($(this), $(this).text());
                              tapped = 1; // change flag to popoever open

                              // don't let link click through
                              e.preventDefault();
                              e.stopImmediatePropagation();

                          }
                          // click on link if same link clicked twice, or call all popovers
                          else {
                              // close all popovers
                              $("#wikipedia a").popover('hide');
                              tapped = 0;

                              // if not the same linked that was clicked, prevent link, but show popover
                              var attr = $(this).attr('aria-describedby');
                              if (attr == false || typeof attr == 'undefined') {
                                  e.preventDefault();
                                  e.stopImmediatePropagation();

                                  getDefinitions($(this), $(this).text());
                                  tapped = 1;
                              }
                              //showLoader();
                          }
                      });


                      $(".hashtag a").on("click", function(e) {
                          // open popover
                          if (tapped == 0) { // if no popover open
                              // show popover
                              getDefinitions($(this), $(this).attr("data-hashtext"));
                              tapped = 1; // change flag to popoever open

                              // don't let link click through
                              e.preventDefault();
                              e.stopImmediatePropagation();

                          }
                          // click on link if same link clicked twice, or call all popovers
                          else {
                              // close all popovers
                              $(".hashtag a").popover('hide');
                              tapped = 0;

                              // if not the same linked that was clicked, prevent link, but show popover
                              var attr = $(this).attr('aria-describedby');
                              if (attr == false || typeof attr == 'undefined') {
                                  e.preventDefault();
                                  e.stopImmediatePropagation();

                                  getDefinitions($(this), $(this).attr("data-hashtext"));
                                  tapped = 1;
                              }
                              //showLoader();
                          }
                      });
                  }

              });


              function getDefinitions(element, term) {
                  $.ajax({
                      url:"definitions.php?term=" + encodeURIComponent(term) + "&label=" + element.attr("data-label"),
                      type:"GET",
                      success:function(result) {
                          def = '';
                          if ((result !== '') && (result !== '<strong>. </strong> ')) {
                              def = result;
                          }
                          else {
                              def = 'No definition found.'
                          }
                          if (element.is(':hover')) {
                              element.attr("data-content", def);


                              $('[data-toggle="popover"]').popover({
                                  boundary: 'window', // Set the boundary to the window
                                  html: true // Enable HTML in popover content (if needed)
                              });

                              element.popover('show');
                          }
                      }
                  });
              }
          }

        </script>

        <?php

        if (!$fromDb) {

        ?>
        
        <script>

            $(document).ready(function() {

                var ele = document.getElementById("lottie");

                lottie.loadAnimation({
                    container: ele, // the dom element that will contain the animation
                    renderer: "svg",
                    loop: true,
                    autoplay: true,
                    path: "assets/img/animation-w500-h500.json" // the path to the animation json
                });

                $.ajax({
                    type:   "POST",
                    url:    "analyze.php",
                    data:   {url: "<?= $url ?>"},
                    success: function(msg) {
                    
                        $("#analytics").html(msg);

                        setupAnalyticsJS();
                       
                    }  
                })

            });

        </script>

        <?php

        } 

        else {

        ?>

        <script>

          setupAnalyticsJS();

        </script>


        <?php

        }

        ?>

        

        <script>
            async function reanalyzeAnalytics(url, container = '#analytics') {
              const el = document.querySelector(container);
              el.innerHTML = `
                <div class="card shadow-sm border-0">
                  <div class="card-body">
                    <div class="placeholder-glow">
                      <span class="placeholder col-7"></span>
                      <span class="placeholder col-4"></span>
                      <span class="placeholder col-6"></span>
                    </div>
                  </div>
                </div>`;

              const form = new URLSearchParams({ url, revalidate: '1' });
              try {
                const res = await fetch('analyze.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: form
                });
                const html = await res.text();
                el.innerHTML = html;
              } catch (e) {
                el.innerHTML = `<div class="alert alert-warning mb-0">Sorry—reanalysis failed.</div>`;
              }
            }
        </script>


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
