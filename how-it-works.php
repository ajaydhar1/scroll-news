<?php
define('BASE_PATH', __DIR__);
$theme_experiment_enabled = true;
require_once BASE_PATH . "/auth/includes/auth_bootstrap.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once BASE_PATH . '/views/partials/___google_analytics.php'; ?>

    <!-- Basics -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Learn how Scroll News works. Understand sentiment, emotions, and narrative frames to analyze how stories are told across sources and over time." />
    <meta name="author" content="Scroll News" />
    <title>How It Works | Scroll News</title>

    <!-- Canonical + favicon -->
    <link rel="canonical" href="https://scrollnews.ai/how-it-works" />
    <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

    <!-- Open Graph -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://scrollnews.ai/how-it-works" />
    <meta property="og:title" content="How Scroll News Works" />
    <meta property="og:description" content="See how Scroll News analyzes sentiment, emotions, and narrative frames to reveal how news stories evolve across sources." />
    <meta property="og:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-how-1200x630.png?v=2" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="https://scrollnews.ai/how-it-works" />
    <meta name="twitter:title" content="How Scroll News Works" />
    <meta name="twitter:description" content="Understand how to read sentiment, emotions, and narrative frames to analyze the news more effectively." />
    <meta name="twitter:image" content="https://scrollnews.ai/assets/img/og/og-scrollnews-how-1200x630.png?v=2" />

    <!-- jQuery min-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <!-- Icons -->
    <script
        src="https://use.fontawesome.com/releases/v6.7.2/js/all.js"
        crossorigin="anonymous"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />
    <!-- Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <!-- Site CSS -->
    <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
    <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />
    <link id="dark-typography-theme" href="/assets/css/dark-typography.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/dark-typography.css'); ?>" rel="stylesheet" />

    <script src="/assets/js/dark-theme.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/dark-theme.js'); ?>"></script>

    <link href="/assets/css/pages/how-it-works.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/pages/how-it-works.css'); ?>" rel="stylesheet" />
</head>

<body id="page-top" class="how-page">

    <!-- Top nav-->
    <?php require_once BASE_PATH . '/views/partials/___topnav_product.php'; ?>

    <div class="how-progress" role="progressbar" aria-label="Story progress" aria-valuemin="1" aria-valuemax="10" aria-valuenow="1">
        <span></span>
    </div>

    <main class="how-story" id="how-story">
        <section class="how-scene how-opening" id="scene-opening" data-story-scene aria-labelledby="title-opening">
            <div class="how-inner how-opening-inner">
                <p class="how-kicker" data-reveal><span>01 / Opening</span><span>How to read this</span></p>
                <h1 id="title-opening" data-reveal>You’re not just reading the news.<br><em>You’re analyzing it.</em></h1>
                <p class="how-deck" data-reveal>One story can sound different depending on who tells it, which details lead, and when you look.</p>
                <a class="how-scroll-cue" href="#scene-idea" data-reveal><span aria-hidden="true">↓</span> Begin with the idea</a>
                <div class="how-opening-mark" aria-hidden="true"><span>READ</span><span>NOTICE</span><span>CONNECT</span></div>
            </div>
        </section>

        <section class="how-scene how-idea" id="scene-idea" data-story-scene aria-labelledby="title-idea">
            <div class="how-inner how-two-column">
                <div data-reveal>
                    <p class="how-index">02 / THE IDEA</p>
                    <h2 id="title-idea">From what happened<br>to how it is told.</h2>
                </div>
                <div class="how-copy" data-reveal>
                    <p class="how-lead">Most news tells you what happened. Scroll News helps you understand how stories are told across sources and over time.</p>
                    <p>Read coverage for the patterns beneath the headlines: tone, framing, and how a story evolves.</p>
                    <div class="how-wordline" aria-label="Patterns, tone, framing, evolution">
                        <span>Patterns</span><span>Tone</span><span>Framing</span><span>Evolution</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="how-scene how-philosophy" id="scene-philosophy" data-story-scene aria-labelledby="title-philosophy">
            <div class="how-inner how-philosophy-inner" data-reveal>
                <p class="how-index">03 / OUR PHILOSOPHY</p>
                <h2 id="title-philosophy">No ads. No outrage.<br><em>Just information — organized.</em></h2>
                <p>Scroll News is designed to help you understand coverage without being pushed, distracted, or pulled into endless reaction cycles.</p>
            </div>
        </section>

        <section class="how-scene how-sentiment" id="scene-sentiment" data-story-scene aria-labelledby="title-sentiment">
            <div class="how-inner how-two-column">
                <div data-reveal>
                    <p class="how-index">04 / SENTIMENT</p>
                    <h2 id="title-sentiment">A reading of tone,<br>not a verdict.</h2>
                    <p class="how-copy">Sentiment summarizes whether an article reads as positive, neutral, or negative. Treat it as a signal to inspect alongside the reporting itself.</p>
                </div>
                <figure class="how-sentiment-figure" data-reveal>
                    <figcaption>Sentiment labels shown in Scroll News</figcaption>
                    <div class="how-sentiment-scale" role="list" aria-label="Positive, neutral, and negative sentiment labels">
                        <div class="how-sentiment-item is-positive" role="listitem"><span class="how-sentiment-dot"></span><span>Positive</span><small>Optimistic tone</small></div>
                        <div class="how-sentiment-item is-neutral" role="listitem"><span class="how-sentiment-dot"></span><span>Neutral</span><small>More even in tone</small></div>
                        <div class="how-sentiment-item is-negative" role="listitem"><span class="how-sentiment-dot"></span><span>Negative</span><small>Critical or concerning</small></div>
                    </div>
                    <p class="how-caption">If sources disagree, the overall picture may feel mixed. “Mixed” is an interpretation, not a separate sentiment label in the current analysis view.</p>
                </figure>
            </div>
        </section>

        <section class="how-scene how-emotions" id="scene-emotions" data-story-scene aria-labelledby="title-emotions">
            <div class="how-inner how-two-column">
                <div data-reveal>
                    <p class="how-index">05 / EMOTIONS</p>
                    <h2 id="title-emotions">Notice the emotional signal.</h2>
                    <p class="how-copy">Emotional reactions add another way to read an article’s tone. They are cues for closer reading, not a substitute for your own judgment.</p>
                </div>
                <div class="how-emotion-board" data-reveal aria-label="Emotion labels used in Scroll News">
                    <span class="how-emotion-label emotion-love">Love</span>
                    <span class="how-emotion-label emotion-angry">Angry</span>
                    <span class="how-emotion-label emotion-ahah">Ahah</span>
                    <span class="how-emotion-label emotion-wow">Wow</span>
                    <span class="how-emotion-label emotion-sad">Sad</span>
                    <p class="how-caption">Labels currently used by the newsroom analysis.</p>
                </div>
            </div>
        </section>

        <section class="how-scene how-frames" id="scene-frames" data-story-scene aria-labelledby="title-frames">
            <div class="how-inner">
                <div class="how-frames-heading" data-reveal>
                    <p class="how-index">06 / NARRATIVE FRAMES</p>
                    <h2 id="title-frames">One event.<br><em>Three ways into the story.</em></h2>
                    <p>A frame is an angle that brings certain questions to the foreground. Compare the perspectives; don’t mistake any single one for the whole story.</p>
                </div>

                <figure class="how-frame-figure" data-reveal>
                    <div class="how-event-node"><span>Illustrative event</span><strong>A city considers a congestion charge</strong></div>
                    <div class="how-frame-branches" aria-label="Three illustrative editorial perspectives on the same event">
                        <article class="how-frame-card frame-economy">
                            <p class="how-frame-label"><span>Perspective 01</span>Economic impact</p>
                            <h3>Who pays, and who benefits?</h3>
                            <p>Consider costs for commuters, local businesses, and the city.</p>
                        </article>
                        <article class="how-frame-card frame-safety">
                            <p class="how-frame-label"><span>Perspective 02</span>Public safety</p>
                            <h3>What changes on the street?</h3>
                            <p>Consider traffic, emergency access, and neighborhood safety.</p>
                        </article>
                        <article class="how-frame-card frame-politics">
                            <p class="how-frame-label"><span>Perspective 03</span>Political strategy</p>
                            <h3>How is the decision defended?</h3>
                            <p>Consider who supports the proposal and how leaders explain it.</p>
                        </article>
                    </div>
                    <figcaption class="how-frame-caption">A fictional teaching example, not Scroll News analysis or a report about a real event.</figcaption>
                </figure>
                <p class="how-frames-footnote" data-reveal>Comparing frames can help you notice emphasis, omissions, and perspective across coverage.</p>
            </div>
        </section>

        <section class="how-scene how-entities" id="scene-entities" data-story-scene aria-labelledby="title-entities">
            <div class="how-inner how-two-column">
                <div data-reveal>
                    <p class="how-index">07 / ENTITIES</p>
                    <h2 id="title-entities">Stories connect through people, places, and organizations.</h2>
                    <p class="how-copy">Select an entity in Scroll News to open its analysis across articles. Follow how the surrounding coverage and sentiment shift over time.</p>
                </div>
                <figure class="how-entity-figure" data-reveal>
                    <figcaption>Illustrative connections around the example event</figcaption>
                    <div class="how-entity-map" role="img" aria-label="Illustrative map connecting a city proposal to commuters, a city council, and a downtown district">
                        <span class="how-entity-node entity-commuters">Commuters</span>
                        <span class="how-entity-node entity-council">City council</span>
                        <span class="how-entity-node entity-center">City proposal</span>
                        <span class="how-entity-node entity-district">Downtown district</span>
                    </div>
                    <p class="how-caption">Example entities only; no indexed coverage is represented here.</p>
                </figure>
            </div>
        </section>

        <section class="how-scene how-time" id="scene-time" data-story-scene aria-labelledby="title-time">
            <div class="how-inner how-two-column">
                <div data-reveal>
                    <p class="how-index">08 / TIME MATTERS</p>
                    <h2 id="title-time">Change the window.<br>See a different span.</h2>
                    <p class="how-copy">Analysis can look across the last 24 hours, 7 days, or 30 days. A wider window gives more history to compare: coverage, sentiment, and narratives can all move.</p>
                </div>
                <div class="how-time-figure" data-reveal>
                    <p class="how-time-label">Open live Politics trends</p>
                    <div class="how-time-option"><span>24h</span><span class="how-time-rule" aria-hidden="true"></span><a href="/analysis.php?context=category&amp;value=politics&amp;w=24h" data-loading>Last 24 hours</a></div>
                    <div class="how-time-option"><span>7d</span><span class="how-time-rule" aria-hidden="true"></span><a href="/analysis.php?context=category&amp;value=politics&amp;w=7d" data-loading>Last 7 days</a></div>
                    <div class="how-time-option"><span>30d</span><span class="how-time-rule" aria-hidden="true"></span><a href="/analysis.php?context=category&amp;value=politics&amp;w=30d" data-loading>Last 30 days</a></div>
                    <p class="how-caption">These links open the current category analysis for Politics. The rules above illustrate the selected window, not article counts.</p>
                </div>
            </div>
        </section>

        <section class="how-scene how-flow" id="scene-flow" data-story-scene aria-labelledby="title-flow">
            <div class="how-inner how-flow-inner">
                <div data-reveal>
                    <p class="how-index">09 / THE QUICK FLOW</p>
                    <h2 id="title-flow">A few steps.<br>A wider view.</h2>
                </div>
                <ol class="how-flow-list" data-reveal>
                    <li><span>01</span>Open an article in the Newsroom.</li>
                    <li><span>02</span>Scan sentiment and emotional signals.</li>
                    <li><span>03</span>Examine the narrative frames.</li>
                    <li><span>04</span>Open an entity to explore related coverage.</li>
                    <li><span>05</span>Change the analysis time window.</li>
                </ol>
                <p class="how-flow-close" data-reveal>Move from reading an article<br><em>to analyzing coverage.</em></p>
                <p class="how-flow-benefits" data-reveal>Spot differences in perspective. Track how stories develop. Compare tone. Ask why coverage feels the way it does.</p>
            </div>
        </section>

        <section class="how-scene how-closing" id="scene-explore" data-story-scene aria-labelledby="title-explore">
            <div class="how-inner how-closing-inner" data-reveal>
                <p class="how-index">10 / START EXPLORING</p>
                <h2 id="title-explore">Read the story.<br><em>Then read around it.</em></h2>
                <p>Open an article, follow an entity, compare a frame, and see what changes with time.</p>
                <a class="how-launch" href="/newsroom.php" data-loading><span>Launch Newsroom</span><span aria-hidden="true">↗</span></a>
            </div>
        </section>
    </main>

    <!-- Footer-->
    <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

    <!-- Modals-->
    <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

    <!-- Bootstrap core JS-->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/pages/how-it-works.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/pages/how-it-works.js'); ?>" defer></script>

</body>

</html>