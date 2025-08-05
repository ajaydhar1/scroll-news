<?php
error_reporting(E_ERROR | E_PARSE);
require_once('___session_results.php');

require_once('___modules.php');
require_once('OpenGraph.php');

// Choose a random article from your RSS feeds
require_once('Feed.php');

$random_article = getRandomArticle();

if (!isset($_GET['url'])) {

    // Redirect to same page with the chosen article as a query param
    header("Location: newsroom.php?url=" . urlencode($random_article['link']) ."&category=" . $random_article['category']);
    exit;
}



$url = $_GET['url'];

// Pull article headline and image using the open graph card

$og = OpenGraph::fetch($url);


$title = 'No Title';
$youtube_search='';
$pub = "Unknown Publisher";
$des = 'No description';
$time = 'Publish date unknown';
$img='';

foreach ($og as $key => $value) {
    if ($key == 'image') {
        $img = $value;
        $img = fix_image_if_broken($img);
    }
    else if ($key == 'title') {
        $title = $value;
        $title = str_replace("â", "'", $title);
        $title = str_replace("", "", $title);
        $title = str_replace("�", "", $title);

        $youtube_search = $title;
    }
    else if ($key == 'site_name') {
        $pub = $value;
    }
    else if ($key == 'description') {
        $des = $value;
    }

}



?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title><?= clean_headline($title) ?></title>
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
        <script src="https://use.fontawesome.com/releases/v5.13.0/js/all.js" crossorigin="anonymous"></script>
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

        <script src="https://www.amcharts.com/lib/3/amcharts.js"></script>
        <script src="https://www.amcharts.com/lib/3/serial.js"></script>
        <script src="https://www.amcharts.com/lib/3/plugins/export/export.min.js"></script>
        <link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all" />
        <script src="https://www.amcharts.com/lib/3/themes/light.js"></script>

        <!-- Add IntroJs styles -->
        <link href="css/introjs.css" rel="stylesheet">
        <?php //<link href="https://unpkg.com/intro.js/minified/introjs.min.css" rel="stylesheet"> ?>

        <link href="css/lightbox.css" rel="stylesheet" />

        <style>

            .custom-highlight {
                background-color: transparent !important;
                opacity: 1 !important;
            }

            header.masthead {
                padding-top: 6.5rem;
                padding-bottom: 6rem;
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

            /* SM Small Device :320px. */
            @media only screen and (min-width: 280px) and (max-width: 1024px) {

              span.link {
                display: none;
              } 

              #side-by-side-panel {
                /* margin-bottom: 60px; */
              }

              #panel-inner-row {
                height: unset !important;
              }
            }

            @media only screen and (max-width: 896px) and (orientation: landscape) {
              .col-md-6.panel {
                height: 100vh !important;
                overflow-y: auto !important;
              }

              .row.flex-row {
                flex-wrap: nowrap !important;
              }

              .sticky-top {
                position: relative;
              }

              #side-by-side-panel {
                margin-bottom: 60px;
              }

              #panel-inner-row {
                height: unset !important;
              }
            }

            ion-icon {
                font-size: 20px;
            }

            .card-body {
              max-height: 600px;
              overflow-y: auto;
            }

            .blue-hover:hover,
            .btn-outline-secondary:hover {
              background-color: #3F51B5 !important;
              color: white !important;
            }

            .popover-header {
                display: none;
            }

            .popover-body {
                background: linear-gradient(to top right, #b91d73, #f953c6);
                color: white;
                border: solid 2px yellow;
                font-family: 'Open Sans', sans-serif;
                line-height: 1.22;
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
                font-size: 18px;
                line-height: calc(var(--line-height-unit) * 1.22) !important;
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

        <div class="loader" style="display: none;"></div>

        <!-- Footer-->
        
        <footer class="footer py-4 bg-white sticky-top">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 text-lg-left">
                        <a class="btn btn-outline-dark analyze-btn" data-toggle="modal" data-target="#analyzeModal">
                            Analyze Article
                        </a>
                    </div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <a data-step="2" data-intro="Click here for information on our analytics." class="btn btn-black btn-social mx-2" title="About" href="about.html"><i class="fas fa-align-right"></i></a>
                        <a data-step="1" data-intro="Welcome to the Scroll News newsroom! Here we provide analytics for the latest news stories. Click this play button to stumble through trending articles." class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" onclick=""><i class="fas fa-play"></i></a>
                        <a data-step="3" data-intro="Click here to see our newsroom video trailer." class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.html"><i class="fas fa-align-left"></i></a>
                    </div>
                    <div class="col-lg-4 text-lg-right font-weight-bold" style="">
                        <a class="btn btn-outline-dark blue-hover browse-btn" data-toggle="modal" data-target="#browseNewsModal">
                            Browse News
                        </a>
                        <button class="btn btn-outline-dark blue-hover browse-btn ml-2" data-toggle="modal" data-target="#searchNewsModal">
                            Search News
                        </button>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Masthead-->
        <header class="masthead" style="background-image: url(<?php echo $img; ?>)">
            <div class="container cover-img py-5">
                <div class="mb-2" style="font-size: 1.25rem;"><strong><a href="" class="<?php if (array_key_exists($_GET['category'], $rss_feeds)) {echo 'category-link';} else {echo 'category-no-link';} ?>" data-category="<?= $_GET['category'] ?>" data-category-url="<?= $rss_feeds[$_GET['category']] ?>">#<?php if (isset($_GET['category'])) { echo $_GET['category']; } else { echo "Article"; } ?></a></strong></div>
                <div class="masthead-subheading"><?php echo $pub; ?></div>
                <div class="masthead-heading text-uppercase"><?php echo clean_headline($title); ?></div>
                <a id="scroll" class="btn btn-green btn-lg btn-rectangle js-scroll-trigger text-black mr-3" href="#">Analytics</a>
                <a class="btn btn-outline-secondary btn-lg btn-rectangle js-scroll-trigger" target='_blank' href="<?php echo $url; ?>" style="color: white; border-color: transparent;">Go to Story</a>
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
              <div id="panel-inner-row" class="row flex-row" style="height: 95vh;">
                <!-- NLP Dashboard Panel -->
                <div class="col-md-6 panel" style="overflow-y: auto; border-right: 2px solid #eee;">
                  <div class="text-center mb-3">
                    <h2>🧠 NLP Dashboard</h2>
                  </div>
                  <div id="analytics">
                    <div id="lottie" class="mb-4"></div>
                    <!-- NLP results (injected from AJAX) will appear here -->
                  </div>
                </div>

                <!-- Article Screenshot Panel -->
                <div class="col-md-6 panel" style="height: 100%; padding: 0; overflow-y: auto;"> <?php //background-color: #fcfcfc; ?>
                  <div class="text-center mb-3">
                    <h2>📰 Article Screenshot</h2>
                  </div>
                  <div class="d-flex justify-content-center align-items-start px-3">
                    
                    <?php 
                        if (!isset($_GET["error"])) {
                    ?>

                                <img 
                                  src="https://news-nlp-api-08865bb82971.herokuapp.com/screenshot?url=<?= urlencode($url) ?>" 
                                  alt="Article Screenshot" 
                                  onload="document.getElementById('img-loader').style.display='none';"
                                  style="max-width: 100%; height: auto; margin-bottom: 20px;"
                                  onerror="window.location.href = 'newsroom.php?url=<?= urlencode($_GET["url"]) ?>&error=1';"
                                />
                                <div id="img-loader" class="text-center mt-3">Loading screenshot...</div>

                    <?php
                        }

                    ?>
                  </div>
                </div>
              </div>

            </div>
        </div>

        <div id="depth-panel" class="row cards builder mt-5">
            <div id="wiki-list-container" class="col-md-12">
                <div id="recurring-news-themes" class="custom-card px-4 py-2" style="min-height:450px; border-radius: 0px;">
                    
                    <div class="text-center mb-3 mt-3">
                        <h2 class="topics">📋 Depth Chart</h2>
                      </div>

                
                    <div id="wiki-list-1" class="">
                        <div class="row mb-5">
                            <div class="col-md-3"></div>
                            <div class="col-md-6"><div id="lottie2"></div></div>
                            <div class="col-md-3"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>


        

        <!-- Footer-->
        <div class="bg-dark" style="height: 338px;">        
            <footer class="footer footer-bottom bg-white py-4">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-4 text-lg-left">Copyright © Scroll News 2025</div>
                        <div class="col-lg-4 my-3 my-lg-0">
                            <a class="btn btn-black btn-social mx-2" title="About" href="about.html"><i class="fas fa-align-right"></i></a>
                            <a class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php"><i class="fas fa-play"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.html"><i class="fas fa-align-left"></i></a>
                        </div>
                        <div class="col-lg-4 text-lg-right font-weight-bold">
                            <a href="index.html">scroll news</a>
                            <br>
                            <a href="terms.html" class="text-muted small mr-3">Terms</a>
                            <a href="privacy.html" class="text-muted small">Privacy</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>


        <div class="modal fade" id="analyzeModal" tabindex="-1" role="dialog" aria-labelledby="analyzeModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title" id="analyzeModalLabel">Analyze Any News Article</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>

              <form id="analyzeForm">
                <div class="modal-body">
                  <div class="form-group">
                    <label for="articleUrl">Enter article URL:</label>
                    <input type="url" class="form-control" id="articleUrl" name="articleUrl" placeholder="https://example.com/article" required>
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="submit" class="btn btn-green text-black">Analyze</button>
                </div>
              </form>

            </div>
          </div>
        </div>


        <div class="modal fade" id="browseNewsModal" tabindex="-1" role="dialog" aria-labelledby="browseNewsLabel" aria-hidden="true" style="">
          <div class="modal-dialog modal-dialog-scrollable modal-xl" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Browse News by Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span>&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <div class="row">
                    <div class="col-lg-5">
                        <div class="mb-3">
                          <label for="categorySelect">Select Category:</label>
                          <select id="categorySelect" class="form-control">
                            <option value="https://rss.app/feeds/tahaOzLGHPxMD9OC.xml">Politics</option>
                            <option value="https://rss.app/feeds/tDmGft5qv7QGmWHv.xml">Business</option>
                            <option value="https://rss.app/feeds/t8coleFVxgPf56NK.xml">Technology</option>
                            <option value="https://rss.app/feeds/tCQMLQm6AHeQ5hJk.xml">Sports</option>
                            <option value="https://rss.app/feeds/tZPiCoHdJqTYlcZc.xml">Health</option>
                            <option value="https://rss.app/feeds/tLSguoVp4t7wa1eJ.xml">Science</option>
                            <option value="https://rss.app/feeds/tBiQM8jJROm1RYn3.xml">Entertainment</option>
                          </select>
                        </div>
                    </div>
                </div>
                <div id="rssArticles" class="row"></div>
              </div>
            </div>
          </div>
        </div>


        <div class="modal fade" id="searchNewsModal" tabindex="-1" role="dialog" aria-labelledby="searchNewsLabel" aria-hidden="true">
          <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title" id="searchNewsLabel">Search News Articles</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span>&times;</span>
                </button>
              </div>

              <div class="modal-body">
                <div class="form-inline mb-3">
                  <input type="text" id="newsSearchInput" class="form-control mr-2" placeholder="Search for news articles..." style="flex: 1;">
                  <button id="searchNewsBtn" class="btn btn-green">Search</button>
                </div>

                <div class="table-responsive">
                  <table id="searchResultsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                      <tr>
                        <th>Headline</th>
                        <th>Publisher</th>
                        <th>Published</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Dynamically populated -->
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>
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
        <?php //<script type="text/javascript" src="https://unpkg.com/intro.js/minified/intro.min.js"></script> ?>

        <script type="text/javascript" src="js/lightbox.js"></script>

        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>


          <script>
            $(document).ready(function() {

            <?php
                if ($url && !isset($_GET["error"])) {
              ?>

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

                      <?php require_once("___get_definitions_js.php"); ?>

                      <?php


                

                echo '

                    if (typeof myArray !== "undefined") {

                        var ele2 = document.getElementById("lottie2");

                        lottie.loadAnimation({
                            container: ele2, // the dom element that will contain the animation
                            renderer: "svg",
                            loop: true,
                            autoplay: true,
                            path: "assets/img/animation-w500-h500.json" // the path to the animation json
                        });

                        
                    }
                    ';



                echo '

                $.ajaxSetup({cache: false});

                if (typeof myArray !== "undefined") {

                    $.ajax({
                        type:   "POST",
                        url:    "wiki-fragments.php",
                        data:   {data: encodeURIComponent(myArray)},
                        success: function(msg) {
                            $("#wiki-list-1").html(msg);
                            //$("#wiki-list-1 .description a").removeAttr("href");
                            $("#wiki-list-1 p sup").remove();
                            $("a[href^=\\"/wiki/\\"]").each(function() {
                                var currentHref = $(this).attr("href");
                                $(this).attr("href", "https://en.wikipedia.org" + currentHref);
                                $(this).attr("target", "_blank");
                                $(this).attr("data-hashtext", $(this).text());
                                $(this).removeAttr("title");
                                $(this).removeAttr("data-original-title");
                            });

                            /*
                            var imageCount = 1;
                            $("#wiki-list-1 img").each(function() {
                                $(this).attr("data-lightbox", imageCount.toString());
                                imageCount++;
                            });
                            */

                            $("#wiki-list-1 .tab-pane").each(function() {
                                $(this).find(".wiki-image").each(function() {
                                    $(this).attr("href", $(this).find("img").attr("src"));
                                });
                            });
  
                            // Re-initialize Lightbox
                            lightbox.init();


                            $("h2 a").each(function() {
                                $(this).attr("data-hashtext", $(this).text());
                            });

                            $(".topics").attr("data-step","11");
                            $(".topics").attr("data-intro","Dimensions of this story. Connect some of these dots with each other and across other stories to develop a mental model of the world.");


                            $("body *").not("#wiki-list-container a").click(function() {
                                $("#wiki-list-container a").popover("hide");
                                tapped = 0;
                            });


                            $("#wiki-list-container a:not(.wiki-image)").each(function(i, obj) {
                                $(this).attr("data-container", "body");
                                $(this).attr("data-toggle", "popover");
                                $(this).attr("data-placement", "auto");
                                $(this).attr("data-content", "temp");
                                $(this).attr("data-html", "true");
                            })



                            if (!isMobile()) {
                                $("#wiki-list-container a:not(.wiki-image)").mouseenter(function() {
                                    getDefinitions($(this), $(this).attr("data-hashtext"));
                                }).mouseleave(function() {
                                    $(this).popover("hide");
                                });
                            }
                            else {

                                $("#wiki-list-container a:not(.wiki-image)").on("click", function(e) {
                                    // open popover
                                    if (tapped == 0) { // if no popover open
                                        // show popover
                                        getDefinitions($(this), $(this).attr("data-hashtext"));
                                        tapped = 1; // change flag to popoever open

                                        // don"t let link click through
                                        e.preventDefault();
                                        e.stopImmediatePropagation();

                                    }
                                    // click on link if same link clicked twice, or call all popovers
                                    else {
                                        // close all popovers
                                        $("#wiki-list-container a:not(.wiki-image)").popover("hide");
                                        tapped = 0;

                                        // if not the same linked that was clicked, prevent link, but show popover
                                        var attr = $(this).attr("aria-describedby");
                                        if (attr == false || typeof attr == "undefined") {
                                            e.preventDefault();
                                            e.stopImmediatePropagation();

                                            getDefinitions($(this), $(this).attr("data-hashtext"));
                                            tapped = 1;
                                        }
                                        //showLoader();
                                    }
                                });
                            }

                        },
                        error: function(msg) {
                            console.log(msg);
                        }
                    })
                }

                ';


            ?>

                  }  
                })

              <?php
                }
              ?>

            });


            $(document).ready(function() {


                $("#scroll").click(function() {
                   scrollToAnchor('analytics');
                });

                if ((<?=$_SESSION["resultViewed"]?> < 2) && ('<?=$_GET['siteSubmit']?>' != 'true')) {
                    introJs().setOptions({
                      highlightClass: 'custom-highlight',
                      overlayOpacity: 0.5  // or 0 if you want no darkening at all
                    }).start();
                }

            });

            function scrollToAnchor(aid){
                var aTag = $("a[name='"+ aid +"']");
                $('html,body').animate({scrollTop: aTag.offset().top - 88},'slow');
            }

          </script>

          <script>
            function goToAnalytics() {

                if (!isMobile()) {
                //place script you don't want to run on mobile here
                    $(".loader").fadeIn("slow");

                }

                
                window.location = "https://gymnastiks.life";

        
            }

            function isMobile() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            }

          </script>


          <script>
              $('#analyzeForm').on('submit', function(e) {
                e.preventDefault();
                const url = $('#articleUrl').val().trim();
                if (url) {
                  const encoded = encodeURIComponent(url);
                  window.location.href = `newsroom.php?url=${encoded}`;
                }
              });
            </script>


          <script>
            function fetchRSSArticles(feedUrl, category) {
              $.ajax({
                url: "rss_proxy.php", // PHP file that fetches RSS content
                method: "POST",
                data: { feed: feedUrl },
                dataType: "json", // This is the key addition
                success: function(response) {
                  const articles = response.items || [];
                  const container = $("#rssArticles");
                  container.empty();

                  if (articles.length === 0) {
                    container.append('<p>No articles found.</p>');
                    return;
                  }

                  articles.forEach(article => {
                    const card = `
                      <div class="col-md-4 mb-4">
                        <div class="card h-100">
                          <img src="${article.image || 'assets/img/news-placeholder.jpg'}" class="card-img-top news-modal" alt="" onerror="this.src = 'assets/img/news-placeholder.jpg';">
                          <div class="card-body d-flex flex-column">
                             <h4 class="card-title mb-2">${article.title}</h4>
                             <p class="card-text text-muted mb-1"><small><a target="_blank" href="https://${article.publisher}">${article.publisher}</a>${article.pubDate ? ' • ' + timeElapsedString(article.pubDate) : ''}</small></p>
                             <p class="card-text">${article.description}</p>
                             <a href="newsroom.php?url=${encodeURIComponent(article.link)}&category=${category}" class="btn btn-green mt-auto">Analyze</a>
                          </div>
                        </div>
                      </div>
                    `;
                    container.append(card);
                  });
                }
              });
            }

            $(document).ready(function() {
              const defaultFeed = $("#categorySelect").val();
              fetchRSSArticles(defaultFeed, "Politics");

              $("#categorySelect").change(function() {
                fetchRSSArticles($(this).val(), $(this).find(":selected").text());
              });
            });





            function timeElapsedString(pubDateStr) {
              const past = new Date(pubDateStr).getTime();

              const now = Date.now();

              if (isNaN(past)) return 'Unknown time';

              let etime = Math.floor((now - past) / 1000); // time difference in seconds

              if (etime < 1) return 'just now';

              const intervals = {
                year: 365 * 24 * 60 * 60,
                month: 30 * 24 * 60 * 60,
                day: 24 * 60 * 60,
                hour: 60 * 60,
                minute: 60,
                second: 1
              };

              for (const [label, seconds] of Object.entries(intervals)) {
                const d = etime / seconds;
                if (d >= 1) {
                  const r = Math.round(d);
                  return `${r} ${label}${r > 1 ? 's' : ''} ago`;
                }
              }
            }




            $(document).on("click", ".category-link", function(e) {
              e.preventDefault();

              // Get category from data attribute
              const category = $(this).data("category");

              <?php

                    // Get the keys of the associative array
                    $keys = array_keys($rss_feeds);

                    // Encode the keys into a JSON string
                    $jsonKeys = json_encode($keys);

                    // Output the JSON string within a JavaScript variable declaration
                    echo "var categoryArray = " . $jsonKeys . ";";
              ?>

              if (categoryArray.includes(category)) {

                  // Get the RSS URL from the data attribute
                  const rssUrl = $(this).data("category-url");

                  // Set the dropdown in the modal to match this URL
                  $("#categorySelect").val(rssUrl);

                  // Open the modal
                  $("#browseNewsModal").modal("show");

                  // Trigger the article fetch
                  fetchRSSArticles(rssUrl);

              }
            });


          </script>

          <script>

            let searchTable;

            $("#searchNewsBtn").click(function () {
              const query = $("#newsSearchInput").val().trim();
              if (!query) return;

              fetchWithRetry(`search_news_proxy.php?q=${encodeURIComponent(query)}`, {
                  method: 'GET',
                  cache: 'no-store'
                })
                //.then(res => res.json())
                .then(data => {
                  if (!data.items) return;

                  const rows = data.items.map(article => {
                      return [
                        article.title,
                        article.publisher,
                        timeElapsedString(new Date(article.pubDate)),
                        `<button class="btn btn-sm btn-green" onclick="analyzeNews('${article.link}')">Analyze</button>`
                      ];
                    });

                    if (!searchTable) {
                      searchTable = $('#searchResultsTable').DataTable({
                        data: rows,
                        columns: [
                          { title: "Title" },
                          { title: "Publisher" },
                          { title: "Published" },
                          { title: "Actions" }
                        ]
                      });
                    } else {
                      searchTable.clear();
                      searchTable.rows.add(rows).draw();
                    }

                })
                .catch(err => {
                  alert("Failed to fetch.");
                  console.error("Fetch error:", err);
                });
            });

            function fetchWithRetry(url, options = {}, retries = 3, delay = 1000) {
              return fetch(url, { cache: 'no-store', ...options })
                .then(res => {
                  if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                  return res.json();
                })
                .catch(err => {
                  if (retries > 0) {
                    return new Promise(resolve => setTimeout(resolve, delay)).then(() =>
                      fetchWithRetry(url, options, retries - 1, delay)
                    );
                  } else {
                    alert("Failed to fetch after retries.");
                    throw err;
                  }
                });
            }

            function analyzeNews(rssLink) {
              fetch(`get_real_url.php?link=${encodeURIComponent(rssLink)}`)
                .then(res => res.json())
                .then(data => {
                  if (data.resolved_url) {
                    window.location.href = `newsroom.php?url=${encodeURIComponent(data.resolved_url)}`;
                  } else {
                    alert("Could not resolve article URL.");
                  }
                });
            }


            document.getElementById("newsSearchInput").addEventListener("keydown", function(event) {
              if (event.key === "Enter") {
                event.preventDefault(); // Prevent form submission if inside a form
                document.getElementById("searchNewsBtn").click(); // Simulate button click
              }
            });

          </script>

          <script src="https://unpkg.com/ionicons@5.2.3/dist/ionicons.js"></script>
          

    </body>
</html>
