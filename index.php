<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Scroll News uses AI to break down U.S. news into fast, readable summaries. Browse trending stories, search headlines, and explore interactive analytics." />
        <meta name="author" content="Scroll News" />
        <title>Scroll News – AI-Powered U.S. News Summaries & Trending Headlines</title>

        <!-- Favicon-->
        <link rel="icon" type="image/png" href="assets/img/play-green.png" />
        <link rel="canonical" href="https://scrollnews.io/">

        <!-- Performance: YouTube preconnects -->
        <link rel="preconnect" href="https://www.youtube.com" crossorigin>
        <link rel="preconnect" href="https://www.google.com" crossorigin>
        <link rel="preconnect" href="https://i.ytimg.com" crossorigin>
        <link rel="preconnect" href="https://yt3.ggpht.com" crossorigin>
        <link rel="dns-prefetch" href="https://s.ytimg.com">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.io/" />
        <meta property="og:title" content="Scroll News — Analyze, Browse, Search the news" />
        <meta property="og:description" content="A smarter way to catch up on the news. Analyze articles by URL, browse top stories by topic, and search the latest headlines." />
        <meta property="og:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-home-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.io/" />
        <meta name="twitter:title" content="Scroll News — Analyze, Browse, Search the news" />
        <meta name="twitter:description" content="A smarter way to catch up on the news. Analyze articles by URL, browse top stories by topic, and search the latest headlines." />
        <meta name="twitter:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-home-1200x630.png" />

        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@graph": [
            {
              "@type": "WebSite",
              "@id": "https://scrollnews.io/#website",
              "url": "https://scrollnews.io/",
              "name": "Scroll News",
              "description": "A smarter way to catch up on the news. Analyze articles by URL, browse stories by topic, and search the latest headlines.",
              "inLanguage": "en",
              "publisher": {
                "@id": "https://scrollnews.io/#organization"
              }
            },
            {
              "@type": "Organization",
              "@id": "https://scrollnews.io/#organization",
              "name": "Scroll News",
              "url": "https://scrollnews.io/",
              "logo": {
                "@type": "ImageObject",
                "url": "https://scrollnews.io/assets/img/logos/scrollnews-icon-512.png"
              }
            },
            {
              "@type": "WebPage",
              "@id": "https://scrollnews.io/#homepage",
              "url": "https://scrollnews.io/",
              "name": "Scroll News — Analyze, Browse, Search the news",
              "description": "A smarter way to catch up on the news. Analyze articles by URL, browse stories by topic, and search the latest headlines.",
              "inLanguage": "en",
              "isPartOf": {
                "@id": "https://scrollnews.io/#website"
              }
            }
          ]
        }
        </script>

        <!-- Font Awesome icons (free version)-->
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

        <!-- Google fonts-->
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css?family=Droid+Serif:400,700,400italic,700italic" rel="stylesheet" type="text/css" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700" rel="stylesheet" type="text/css" />
    
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@700&family=Lato&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600&family=Inter&display=swap" rel="stylesheet">
    
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet" />
        <link href="css/custom.css" rel="stylesheet" />

        <link href="css/lightbox.css" rel="stylesheet" />

        <link rel="preload" as="image" href="assets/img/header-bg-3.jpg">

        <style>

            html {
                scroll-behavior: smooth;
            }

            header.masthead {
                background-image: linear-gradient(rgba(0, 0, 0, 0.72), rgba(0, 0, 0, 0.72)), url(assets/img/header-bg-3.jpg);
            }

            a {
                color: var(--brand-color);
            }

            .timeline > li .timeline-image {
                background-color: var(--brand-color);
            }

            .btn-lg, .btn-group-lg > .btn {
                font-size: 1.2rem;
            }

            .btn {
                box-shadow: 0 0 0 0.14rem var(--brand-color) !important;
            }

            .btn.btn-rectangle {
                box-shadow: 0 0 0 0.14rem var(--brand-color) !important;
            }

            .blue-hover:hover,
            .btn-outline-secondary:hover {
              background-color: #3F51B5 !important;
              color: white !important;
            }

            #portfolio .portfolio-item .portfolio-link .portfolio-hover {
                background: transparent;
            }

            section.py-5 h3 {
                font-size: 1.4rem;
                color: #444;
                font-weight: 500;
            }

            section#contact {29;
                background-image: url(assets/img/laptop-sparkle.jpg);
            }

            section#playlists {
                scroll-margin-top: 95px; /* Adjust the offset as needed */
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

        <!-- topnav-->
        <footer class="footer py-4 bg-white sticky-top sn-top-nav">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 text-lg-left text-bolder">
                        <h5 class="mb-0">
                            <a href="index.php">
                                <img src="assets/img/play-green.png" alt="Logo play button" style="height: 24px; width: auto; vertical-align: middle; margin-right: 5px; margin-bottom: 5px;">
                                Scroll News
                            </a>
                        </h5>
                    </div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <a class="btn btn-black btn-social mx-2" title="About" href="about.html"><i class="fas fa-align-right"></i></a>
                        <a class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" onclick="" data-loading><i class="fas fa-play"></i></a>
                        <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.html"><i class="fas fa-align-left"></i></a>
                    </div>
                    <div class="col-lg-4 text-lg-right" style=""><a href="about.html">About</a></div>
                </div>
            </div>
        </footer>

        <!-- Masthead-->
        <header class="masthead">
            <div class="container">
                <div class="masthead-subheading">Welcome To Scroll News!</div>
                <div class="masthead-heading text-uppercase">AI-Driven News Summaries</div>
                <div class="text-center">
                    <a id="scroll" class="btn btn-green btn-lg btn-rectangle js-scroll-trigger text-black d-block d-md-inline-block mb-3 mr-md-2" href="newsroom.php" data-loading>Launch Newsroom</a>
                    <a class="btn btn-outline-secondary btn-lg btn-rectangle js-scroll-trigger d-block d-md-inline-block mb-3" href="#playlists" style="color: white; border-color: transparent;">Watch Video</a>
                </div>
            </div>
        </header>
        <!-- Services-->
        <section class="page-section" id="services">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-heading text-uppercase">Features</h2>
                    <h3 class="section-subheading text-muted"> <!--Lorem ipsum dolor sit amet consectetur.--></h3>
                </div>
                <div class="row text-center">
                    <div class="col-md-4">
                        <span class="fa-stack fa-4x">
                            <i class="fas fa-circle fa-stack-2x text-green"></i>
                            <i class="fas fa-wand-magic-sparkles fa-stack-1x fa-inverse"></i>
                        </span>
                        <h4 class="my-3">AI-powered Summaries</h4>
                        <!--<p class="text-muted">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Minima maxime quam architecto quo inventore harum ex magni, dicta impedit.</p>-->
                    </div>
                    <div class="col-md-4">
                        <span class="fa-stack fa-4x">
                            <i class="fas fa-circle fa-stack-2x text-green"></i>
                            <i class="fas fa-street-view fa-stack-1x fa-inverse"></i>
                        </span>
                        <h4 class="my-3">Effortless Scrolling</h4>
                        <!--<p class="text-muted">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Minima maxime quam architecto quo inventore harum ex magni, dicta impedit.</p>-->
                    </div>
                    <div class="col-md-4">
                        <span class="fa-stack fa-4x">
                            <i class="fas fa-circle fa-stack-2x text-green"></i>
                            <i class="fas fa-mask fa-stack-1x fa-inverse"></i>
                        </span>
                        <h4 class="my-3">Tap-to-Play Articles</h4>
                        <!--<p class="text-muted">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Minima maxime quam architecto quo inventore harum ex magni, dicta impedit.</p>-->
                    </div>
                </div>
            </div>
        </section>
        <!-- Portfolio Grid-->
        <section class="page-section bg-light" id="portfolio">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-heading text-uppercase">Newsroom Modules</h2>
                    <h3 class="section-subheading text-muted"> <!--Lorem ipsum dolor sit amet consectetur.--></h3>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-4 col-sm-6 mb-4">
                        <!-- Portfolio item 1-->
                        <div class="portfolio-item">
                            <a class="portfolio-link" href="assets/img/portfolio/analyze-article.jpg" data-lightbox="modules">
                                <img class="img-fluid" src="assets/img/portfolio/analyze-article.jpg" alt="Screenshot of the Analyze Article tool with a URL input box" data-lightbox="1"/>
                            </a>
                            <div class="portfolio-caption">
                                <div class="portfolio-caption-heading">Analyze Article</div>
                                <!--<div class="portfolio-caption-subheading text-muted">Illustration</div>-->
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 mb-4">
                        <!-- Portfolio item 2-->
                        <div class="portfolio-item">
                            <a class="portfolio-link" href="assets/img/portfolio/browse-news.jpg" data-lightbox="modules">
                                <img class="img-fluid" src="assets/img/portfolio/browse-news.jpg" alt="Screenshot of the article grid organized by topic in Scroll News" data-lightbox="2"/>
                            </a>
                            <div class="portfolio-caption">
                                <div class="portfolio-caption-heading">Browse News</div>
                                <!--<div class="portfolio-caption-subheading text-muted">Graphic Design</div>-->
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 mb-4">
                        <!-- Portfolio item 3-->
                        <div class="portfolio-item">
                            <a class="portfolio-link" href="assets/img/portfolio/search-news.jpg" data-lightbox="modules">
                                <img class="img-fluid" src="assets/img/portfolio/search-news.jpg" alt="Screenshot of news search results in Scroll News" data-lightbox="3"/>
                            </a>
                            <div class="portfolio-caption">
                                <div class="portfolio-caption-heading">Search News</div>
                                <!--<div class="portfolio-caption-subheading text-muted">Identity</div>-->
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 mb-4 mb-lg-0">
                        <!-- Portfolio item 4-->
                        <div class="portfolio-item">
                            <a class="portfolio-link" href="assets/img/portfolio/nlp-dashboard.jpg" data-lightbox="modules">
                                <img class="img-fluid" src="assets/img/portfolio/nlp-dashboard.jpg" alt="Screenshot of the NLP dashboard with charts and article stats" data-lightbox="4"/>
                            </a>
                            <div class="portfolio-caption">
                                <div class="portfolio-caption-heading">NLP Dashboard</div>
                                <!--<div class="portfolio-caption-subheading text-muted">Branding</div>-->
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 mb-4 mb-sm-0">
                        <!-- Portfolio item 5-->
                        <div class="portfolio-item">
                            <a class="portfolio-link" href="assets/img/portfolio/article-screenshot.jpg" data-lightbox="modules">
                                <img class="img-fluid" src="assets/img/portfolio/article-screenshot.jpg" alt="Screenshot of a news article captured by Scroll News" data-lightbox="5"/>
                            </a>
                            <div class="portfolio-caption">
                                <div class="portfolio-caption-heading">Article Screenshot</div>
                                <!--<div class="portfolio-caption-subheading text-muted">Website Design</div>-->
                            </div>
                        </div>
                    </div>
                    <?php /*
                    <div class="col-lg-4 col-sm-6">
                        <!-- Portfolio item 6-->
                        <div class="portfolio-item">
                            <a class="portfolio-link" href="assets/img/portfolio/depth-chart.jpg" data-lightbox="modules">
                                <img class="img-fluid" src="assets/img/portfolio/depth-chart.jpg" alt="..." data-lightbox="6"/>
                            </a>
                            <div class="portfolio-caption">
                                <div class="portfolio-caption-heading">Depth Chart</div>
                                <!--<div class="portfolio-caption-subheading text-muted">Photography</div>-->
                            </div>
                        </div>
                    </div>
                    */ ?>
                </div>
            </div>
        </section>
        <!-- About-->
        <section class="page-section" id="story">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-heading text-uppercase">Our Story</h2>
                    <h3 class="section-subheading text-muted"> <!--Lorem ipsum dolor sit amet consectetur.--></h3>
                </div>
                <ul class="timeline">
                    <li>
                        <div class="timeline-image"><img class="rounded-circle img-fluid" src="assets/img/about/nlp.jpg" alt="Team climbing a mountain, representing the early NLP news project" /></div>
                        <div class="timeline-panel">
                            <div class="timeline-heading">
                                <h4>October 2017</h4>
                                <h4 class="subheading">Our Humble Beginnings</h4>
                            </div>
                            <div class="timeline-body"><p class="text-muted">Looking to launch our first startup, we started developing our first cut at a news platform that performed NLP analyses of online news articles. It was an incredibly eye-opening project that taught us a lot about startups and technology!</p></div>
                        </div>
                    </li>
                    <li class="timeline-inverted">
                        <div class="timeline-image"><img class="rounded-circle img-fluid" src="assets/img/about/inflection.jpg" alt="Friends playing a board game, representing a turning point in our product" /></div>
                        <div class="timeline-panel">
                            <div class="timeline-heading">
                                <h4>March 2018</h4>
                                <h4 class="subheading">An Inflection Point</h4>
                            </div>
                            <div class="timeline-body"><p class="text-muted">Up until this time, we were paying a third-party vendor to use their NLP API for webpage and raw text analysis. Then, in an effort to save money and reduce dependencies, we decided to develop our own NLP API in-house!</p></div>
                        </div>
                    </li>
                    <li>
                        <div class="timeline-image"><img class="rounded-circle img-fluid" src="assets/img/about/tennis.jpg" alt="Man playing tennis, symbolizing opening the platform to new ideas" /></div>
                        <div class="timeline-panel">
                            <div class="timeline-heading">
                                <h4>Febuary 2020</h4>
                                <h4 class="subheading">Opening the Platform</h4>
                            </div>
                            <div class="timeline-body"><p class="text-muted">In 2020, we started exploring new ways of leveraging our NLP API. We wanted to create an echo chamber of news and media products that did NLP analyses of online content. We started by creating a website that analyzed sports articles using the StumbleUpon UX!</p></div>
                        </div>
                    </li>
                    <li class="timeline-inverted">
                        <div class="timeline-image"><img class="rounded-circle img-fluid" src="assets/img/about/creating-scroll-news.jpg" alt="Young man in a suit, representing the launch of Scroll News" /></div>
                        <div class="timeline-panel">
                            <div class="timeline-heading">
                                <h4>July 2025</h4>
                                <h4 class="subheading">Creation of Scroll News</h4>
                            </div>
                            <div class="timeline-body"><p class="text-muted">Due to the massive code bloat of our first news product, we decided to turn the project into a learning experience and develop a newer, more focused news platform called Scroll News, that simplifies and streamlines the news consumption process. Which brings us to today! Welcome to Scroll News!</p></div>
                        </div>
                    </li>
                    <li class="timeline-inverted">
                        <div class="timeline-image d-flex">
                            <h4 class="my-0 mx-auto my-auto">
                                Be Part
                                <br />
                                Of Our
                                <br />
                                Story!
                            </h4>
                        </div>
                    </li>
                </ul>
            </div>
        </section>


        <?php require_once("___scroll_strip.php"); ?>


        <section id="playlists" class="pt-4 pb-5 pt-sm-5">
            <div class="container">
                <div style="max-width:880px;margin:auto">
                    <label for="ytTab" style="display:block;margin:0 0 8px">News playlists</label>
                    <select id="ytTab" style="width:100%;padding:8px">
                        <option value="PLQOa26lW-uI8H1WxYPbSEqQJYoTRq2h5n">ABC News (Daily News Updates)</option>
                        <option value="PL0tDb4jw6kPz6KY3KYoZ5bRLdMAEzpSbb">NBC News (Top News)</option>
                        <option value="PLEb3ThbkPrFazUgt4b5WwVCj9QpaflUbl">CBS News (Top News)</option>
                        <option value="PLGaYlBJIOoa9DV4I6sC8R8bX4L0Jq16XZ">Bloomberg (Stock Market News and Analysis)</option>
                        <option value="PLGaYlBJIOoa9aFYxidijF94vLKdDb04El">Bloomberg (Tech News)</option>
                        <option value="PLVbP054jv0KrD7L2lIuW8WuQK9--rAAgx">CNBC (Squawk Box)</option>
                        <option value="PLVbP054jv0KpzbW7Mh-JxOWaRs_PWNBS6">CNBC (Squawk On The Street)</option>
                        <option value="PLJ8IrgLlRTdgCt-WeomGIddeL9IhfvoeH">CNBC International (Squawk Box Europe)</option>
                        <option value="PLv1qHE0zuJL-EqaXwwo6Le34uycJnNCsB">Fox Business (Mornings with Maria)</option>
                        <option value="PLv1qHE0zuJL_99FPlL25gsQ1FvbbAP3pX">Fox Business (The Big Money Show)</option>
                        <option value="PLn3nHXu50t5wkud7Iv0LFazfV8dja6dc3">ESPN (First Take)</option>
                        <option value="PLn3nHXu50t5xU9FvI2M2km5a4GgfqfKlY">ESPN (Get Up)</option>
                        <option value="PLGmceqLQ0UeYSzvsA6agwpkdoCMySiLA6">Fox News (Trump Administration)</option>
                        <option value="PLDIVi-vBsOExM37bFPYowCBBiohZCV1iC">MSNBC (The Latest)</option>
                        <!-- Paste more playlist URLs or IDs as options; ID or full URL both work -->
                        <!-- <option value="https://www.youtube.com/playlist?list=PLxxxx">World</option> -->
                        <!-- <option value="PLyyyy">Technology</option> -->
                    </select>

                    <div style="position:relative;padding-top:56.25%;margin-top:12px;border-radius:12px;overflow:hidden">
                        <iframe id="ytFrame" allow="autoplay; encrypted-media" allowfullscreen
                            style="position:absolute;inset:0;width:100%;height:100%;border:0"
                            src="about:blank"></iframe>
                    </div>
                </div>

                <script>
                    const frame = document.getElementById('ytFrame');
                    const sel   = document.getElementById('ytTab');

                    const toPlaylistId = (val) => {
                        // Accept raw IDs or full playlist URLs (?list=...)
                        try { 
                            const u = new URL(val);
                            return u.searchParams.get('list') || val;
                        } catch { return val; }
                    };

                    const embed = (plId) =>
                    `https://www.youtube.com/embed/videoseries?list=${encodeURIComponent(plId)}&rel=0&modestbranding=1`;

                    const load = () => (frame.src = embed(toPlaylistId(sel.value)));

                    sel.addEventListener('change', load);
                    load(); // init on first render
                </script>
            </div>
        </section>


        <!-- Team-->
        <section class="page-section bg-light" id="team">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-heading text-uppercase">Meet The Team</h2>
                    <!--<h3 class="section-subheading text-muted">Lorem ipsum dolor sit amet consectetur.</h3>-->
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="team-member">
                            <img class="mx-auto rounded-circle" src="assets/img/team/parveen-anand.jpg" alt="Illustrated headshot of Parveen Anand, Lead Developer" />
                            <h4>Parveen Anand</h4>
                            <p class="text-muted">Lead Developer</p>
                            <a class="btn btn-dark btn-social mx-2" href="#!" aria-label="Parveen Anand Twitter Profile"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!" aria-label="Parveen Anand Facebook Profile"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!" aria-label="Parveen Anand LinkedIn Profile"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="team-member">
                            <img class="mx-auto rounded-circle" src="assets/img/team/matt-elsher.jpg" alt="Illustrated headshot of Matt Elsher, Product Manager" />
                            <h4>Matt Elsher</h4>
                            <p class="text-muted">Product Manager</p>
                            <a class="btn btn-dark btn-social mx-2" href="#!" aria-label="Larry Parker Twitter Profile"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!" aria-label="Larry Parker Facebook Profile"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!" aria-label="Larry Parker LinkedIn Profile"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="team-member">
                            <img class="mx-auto rounded-circle" src="assets/img/team/diana-keri.jpg" alt="Illustrated headshot of Diana Keri, Lead Marketer" />
                            <h4>Diana Keri</h4>
                            <p class="text-muted">Lead Marketer</p>
                            <a class="btn btn-dark btn-social mx-2" href="#!" aria-label="Diana Petersen Twitter Profile"><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!" aria-label="Diana Petersen Facebook Profile"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-dark btn-social mx-2" href="#!" aria-label="Diana Petersen LinkedIn Profile"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
                <!--
                <div class="row">
                    <div class="col-lg-8 mx-auto text-center"><p class="large text-muted">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aut eaque, laboriosam veritatis, quos non quis ad perspiciatis, totam corporis ea, alias ut unde.</p></div>
                </div>
                -->
            </div>
        </section>


        <!-- Trusted by creators text section -->
        <section class="py-5">
            <div class="container text-center">
                <h3 class="mb-0">Trusted by modern creators and teams</h3>
            </div>
        </section>
        <!-- Contact-->
        <section class="page-section" id="contact">
            <div class="container">
                <div class="text-center">
                    <h2 class="section-heading text-uppercase">&nbsp;</h2>
                    <h3 class="section-subheading text-muted">&nbsp;</h3>
                </div>
                
            </div>
        </section>
        <!-- Footer-->   
        <div class="bg-dark" style="height: 338px;">        
            <footer class="footer footer-bottom bg-white py-4">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-4 text-lg-left">Copyright © Scroll News 2025</div>
                        <div class="col-lg-4 my-3 my-lg-0">
                            <a class="btn btn-black btn-social mx-2" title="About" href="about.html"><i class="fas fa-align-right"></i></a>
                            <a class="btn btn-green btn-social mx-2" title="Stumble through articles" href="newsroom.php" data-loading><i class="fas fa-play"></i></a>
                            <a class="btn btn-black btn-social mx-2" title="Control Room" href="control-room.html"><i class="fas fa-align-left"></i></a>
                        </div>
                        <div class="col-lg-4 text-lg-right font-weight-bold">
                            <a href="index.php">scroll news</a>
                            <br>
                            <a href="terms.html" class="text-muted small mr-3">Terms</a>
                            <a href="privacy.html" class="text-muted small">Privacy</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <?php //<script src="js/scripts.js"></script> ?>

        <script type="text/javascript" src="js/lightbox.js"></script>
        <!-- * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *-->
        <!-- * *                               SB Forms JS                               * *-->
        <!-- * * Activate your form at https://startbootstrap.com/solution/contact-forms * *-->
        <!-- * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *-->
        <?php //<script src="https://cdn.startbootstrap.com/sb-forms-latest.js"></script> ?>

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
