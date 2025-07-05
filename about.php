<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="About Gymnastiks Life." />
        <meta name="author" content="Gymnastiks Life" />
        <title>Scroll News - About the Analytics</title>
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

        <style>

            :root {
                --circle-btn-outline-color: deeppink;
                --btn-outline-color: #415158; /* Slate Gray */
                --link-color: #00be7c; /* Bright Green */
                /* --link-color: #838685; Light Gray */

            }

            .content {
                position: fixed;
                bottom: 0;
                left: 0;
                /* background: rgba(255, 255, 255, 1); */
                color: #0a0a0a;
                width: 100%;
                padding: 20px;
                height: 190px;
                margin-top: -95px;
                text-align: center;
            }

            section#services {
                background: #eee;
            }

            p {
                line-height: 1.5;
                font-weight: 300;
            }

            .page-section h3.section-subheading {
                font-weight: 700;
            }

            a {
                color: var(--link-color);
            }

            footer .text-lg-right a {
                color: mediumaquamarine;
            }

        </style>

    </head>
    <body id="page-top">

        <div class="loader" style="display: none;"></div>

        <?php require('___footer.php'); ?>
        
        <!-- Services-->
        <section class="page-section" id="services" style="padding: 4rem 0;">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 text-center">
                        <h2 class="section-heading text-uppercase">About the Analytics</h2>
                        <h3 class="section-subheading" style="margin-bottom: .75rem;">Scroll News provides analytics for the latest news articles. At the core of the website is a news analysis framework that uses machine learning to break down news stories into their fundamental contents.</h3>
                        <h3 class="section-subheading" style="margin-bottom: 3.15rem;">There is no better way to learn about what is happening in the world than using our platform!</h3>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-md-4">
                        <span class="fa-stack fa-4x">
                            <i class="fas fa-circle fa-stack-2x text-dark"></i>
                            <i class="fas fa-flag-usa fa-stack-1x fa-inverse"></i>
                        </span>
                        <h4 class="my-3">US</h4>
                        <p class="text-muted">This site is geared toward the US. It was designed with people in mind who live in the US, or enjoy reading US news.</p>
                    </div>
                    <div class="col-md-4">
                        <span class="fa-stack fa-4x">
                            <i class="fas fa-circle fa-stack-2x text-pink"></i>
                            <i class="fas fa-newspaper fa-stack-1x fa-inverse"></i>
                        </span>
                        <h4 class="my-3">News</h4>
                        <p class="text-muted">We source only the latest trending news from local and national news sources. If the story is important, we have it covered.</p>
                    </div>
                    <div class="col-md-4">
                        <span class="fa-stack fa-4x">
                            <i class="fas fa-circle fa-stack-2x" style="color: mediumaquamarine"></i>
                            <i class="fas fa-chart-bar fa-stack-1x fa-inverse"></i>
                        </span>
                        <h4 class="my-3">Analytics</h4>
                        <p class="text-muted">We distill the important people, places, narrative metrics, and more, and put these key puzzle pieces right on your dashboard.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Bootstrap core JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
        <!-- Third party plugin JS-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
        <!-- Contact form JS-->
        <script src="assets/mail/jqBootstrapValidation.js"></script>
        <script src="assets/mail/contact_me.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>

        <script>

            function goToAnalytics() {
                if (!isMobile()) {
                //place script you don't want to run on mobile here
                    $(".loader").fadeIn("slow");
                }
            }

            function isMobile() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            }
    
        </script>

    </body>
</html>
