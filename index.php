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
    header("Location: index.php?url=" . urlencode($random_article['link']) ."&category=" . $random_article['category']);
    exit;
}


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Scroll News - News Analytics</title>
        <link rel="icon" type="image/png" href="assets/img/play-pink.png" />
        <!-- Font Awesome icons (free version)-->
        <script src="https://use.fontawesome.com/releases/v5.13.0/js/all.js" crossorigin="anonymous"></script>
        <!-- Google fonts-->
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css?family=Droid+Serif:400,700,400italic,700italic" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700" rel="stylesheet" type="text/css" />
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

        <style>

            :root {
                --circle-btn-outline-color: #00bfa6;
                --btn-outline-color: white; /* Slate Gray */
                --link-color: #00bfa6; /* Bright Green */
                /* --link-color: #838685; Light Gray */

            }

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
                box-shadow: 0 0 0 0.14rem var(--circle-btn-outline-color) !important;
            }

            .btn.btn-rectangle {
                box-shadow: 0 0 0 0.14rem var(--btn-outline-color) !important;
            }

            .container.cover-img {      
                /* background-color: rgba(0, 247, 216, 0.8); */
                /* background-color: rgba(96, 125, 139, 0.8); */ /* #607D8B */
                background-color: rgb(20 20 20 / 80%);
                width: 100%;
                height: 100%;
            }

            a {
                color: var(--link-color);
            }

            footer .text-lg-right a {
                color: #00bfa6;
            }

            footer .text-lg-right a:hover {
                color: black;
            }

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
            @media only screen and (min-width: 280px) and (max-width: 499px) {
              #scroll {
                margin-right: 0 !important;
                /* margin-bottom: 19px !important; */
              } 
            }

            /* SM Small Device :320px. */
            @media only screen and (min-width: 280px) and (max-width: 1024px) {
              .footer-bottom {
                display: none;
              }

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

            .btn-outline-secondary:hover {
              background-color: #3F51B5;
              color: white;
            }

            .popover-body {
                background: linear-gradient(to top right, #b91d73, #f953c6);
                color: white;
                border: solid 2px yellow;
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
                border: solid 2px yellow !important;
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

            .analyze-btn {
                box-shadow: 0 0 0 0.14rem var(--link-color-break) !important;
            }

            .analyze-btn:hover {
                background: #00bfa6;
            }


        </style>


    </head>
    <body id="page-top">

        <div class="loader" style="display: none;"></div>

        <?php 

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

        <!-- Footer-->
        
        <footer class="footer py-4 bg-white sticky-top">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 text-lg-left">
                        <a class="btn btn-outline-secondary analyze-btn mx-2" data-toggle="modal" data-target="#analyzeModal">
                          Analyze Article
                        </a>
                    </div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <a data-step="2" data-intro="Click here for information on our analytics." class="btn btn-black btn-social mx-2" href="about.php"><i class="fas fa-align-right"></i></a>
                        <a data-step="1" data-intro="Welcome to Scroll News! We provide analytics for the latest news stories. Click this play button to scroll through trending articles." class="btn btn-black btn-social mx-2" href="index.php?url=<?= urlencode($random_article['link']) ?>&category=<?= $random_article['category'] ?>" onclick=""><i class="fas fa-play"></i></a>
                        <a data-step="3" data-intro="Click here to see our newsroom video trailer." class="btn btn-black btn-social mx-2" href="newsroom.php"><i class="fas fa-align-left"></i></a>
                    </div>
                    <div class="col-lg-4 text-lg-right font-weight-bold" style="color: var(--link-color);"><a href="index.php?url=<?= urlencode($random_article['link']) ?>&category=<?= $random_article['category'] ?>">scroll news</a></div>
                </div>
            </div>
        </footer>

        <!-- Masthead-->
        <header class="masthead" style="background-image: url(<?php echo $img; ?>)">
            <div class="container cover-img py-5">
                <div class="mb-2" style="color: #00bfa6; font-size: 1.25rem;"><strong>#<?php if (isset($_GET['category'])) { echo $_GET['category']; } else { echo "Article"; } ?></strong></div>
                <div class="masthead-subheading"><?php echo $pub; ?></div>
                <div class="masthead-heading text-uppercase"><?php echo clean_headline($title); ?></div>
                <a id="scroll" class="btn btn-green btn-lg btn-rectangle js-scroll-trigger text-dark mr-3" href="#">Analytics</a>
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
                    <h3>🧠 NLP Dashboard</h3>
                  </div>
                  <div id="analytics">
                    <div id="lottie" class="mb-4"></div>
                    <!-- NLP results (injected from AJAX) will appear here -->
                  </div>
                </div>

                <!-- Article Screenshot Panel -->
                <div class="col-md-6 panel" style="height: 100%; padding: 0; overflow-y: auto;"> <?php //background-color: #fcfcfc; ?>
                  <div class="text-center mb-3">
                    <h3>📰 Article Screenshot</h3>
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
                                  onerror="window.location.href = 'index.php?url=<?= urlencode($_GET["url"]) ?>&error=1';"
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
                        <h3 class="topics">📋 Depth Chart</h3>
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
        
        <footer class="footer footer-bottom py-4">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 text-lg-left">Copyright © Scroll News <?= date("Y") ?></div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <!--<a class="btn btn-light btn-social mx-2" href="#"><i class="fab fa-twitter"></i></a>-->
                        <a class="btn btn-black btn-social mx-2" href="about.php"><i class="fas fa-align-right"></i></a>
                        <a class="btn btn-black btn-social mx-2" href="index.php?url=<?= urlencode($random_article['link']) ?>&category=<?= $random_article['category'] ?>" onclick=""><i class="fas fa-play"></i></a>
                        <a class="btn btn-black btn-social mx-2" href="newsroom.php"><i class="fas fa-align-left"></i></a>
                        <!--
                        <a class="btn btn-light btn-social mx-2" href="#"><ion-icon name="logo-instagram"></ion-icon></a>-->
                    </div>
                    <div class="col-lg-4 text-lg-right font-weight-bold" style="color: var(--link-color);"><a href="index.php?url=<?= urlencode($random_article['link']) ?>&category=<?= $random_article['category'] ?>">scroll news</a></div>
                </div>
            </div>
        </footer>


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
                  <button type="submit" class="btn btn-green text-dark">Analyze</button>
                </div>
              </form>

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
                        $("#insta-icon").css("color", "var(--link-color)");
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
                        $("#twitter-icon").css("color", "var(--link-color)");
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
                        $("#google-icon").css("color", "var(--link-color)");
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
                        $("#youtube-icon").css("color", "var(--link-color)");
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
                            });

                            $(".topics").attr("data-step","11");
                            $(".topics").attr("data-intro","Dimensions of this story. Connect some of these dots with each other and across other stories to develop a mental model of the world.");


                            $("body *").not(".description a").click(function() {
                                $(".description a").popover("hide");
                                tapped = 0;
                            });


                            $(".description a").each(function(i, obj) {
                                $(this).attr("data-container", "body");
                                $(this).attr("data-toggle", "popover");
                                $(this).attr("data-placement", "auto");
                                $(this).attr("data-content", "temp");
                                $(this).attr("data-html", "true");
                            })



                            if (!isMobile()) {
                                $(".description a").mouseenter(function() {
                                    getDefinitions($(this), $(this).attr("data-hashtext"));
                                }).mouseleave(function() {
                                    $(this).popover("hide");
                                });
                            }
                            else {

                                $(".description a").on("click", function(e) {
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
                                        $(".description a").popover("hide");
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
                  window.location.href = `index.php?url=${encoded}`;
                }
              });
            </script>



          <script src="https://unpkg.com/ionicons@5.2.3/dist/ionicons.js"></script>
          

    </body>
</html>
