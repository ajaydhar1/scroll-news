<?php
define('BASE_PATH', __DIR__);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Basics -->
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Learn how Scroll News works. Understand sentiment, emotions, and narrative frames to analyze how stories are told across sources and over time." />
        <meta name="author" content="Scroll News" />
        <title>How It Works | Scroll News</title>

        <!-- Canonical + favicon -->
        <link rel="canonical" href="https://scrollnews.io/how-it-works" />
        <link rel="icon" type="image/png" href="/assets/img/play-green.png" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://scrollnews.io/how-it-works" />
        <meta property="og:title" content="How Scroll News Works" />
        <meta property="og:description" content="See how Scroll News analyzes sentiment, emotions, and narrative frames to reveal how news stories evolve across sources." />
        <meta property="og:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-how-1200x630.png" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://scrollnews.io/how-it-works" />
        <meta name="twitter:title" content="How Scroll News Works" />
        <meta name="twitter:description" content="Understand how to read sentiment, emotions, and narrative frames to analyze the news more effectively." />
        <meta name="twitter:image" content="https://scrollnews.io/assets/img/og/og-scrollnews-how-1200x630.png" />

        <!-- jQuery min-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

        <!-- Icons -->
        <script
            src="https://use.fontawesome.com/releases/v6.7.2/js/all.js"
            crossorigin="anonymous"
        ></script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

        <!-- Site CSS -->
        <link href="/assets/css/styles.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/styles.css'); ?>" rel="stylesheet" />
        <link href="/assets/css/custom.css?v=<?php echo filemtime(BASE_PATH . '/assets/css/custom.css'); ?>" rel="stylesheet" />

        <!-- Page-specific styles -->
        <style>
            body#page-top { background: #fafafa; }

            @media (min-width: 768px) {
            section {
                padding: 0;
            }
            }

            a { color: var(--brand-color); }

        </style>
    </head>
    <body id="page-top">

        <!-- Top nav-->
        <?php require_once BASE_PATH . '/views/partials/___topnav_full.php'; ?>

        <!-- Content area-->
        <div class="container my-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-5">

                    <header class="mb-5">
                        <h1 class="mb-3">📰 Scroll News</h1>
                        <p class="text-muted mb-0">
                            <strong>How to Read This</strong>
                        </p>
                    </header>

                    <blockquote>
                        You’re not just reading the news—you’re analyzing it.
                    </blockquote>

                    <hr class="my-5">

                    <section class="mb-5">
                        <h4 class="fw-bold mb-3">🧠 The Idea</h4>
                        <p>
                            Most news shows you <strong>what happened.</strong>
                        </p>
                        <p>
                            Scroll News shows you:
                        </p>
                        <blockquote>
                            <p>
                                <strong>how the story is being told across sources, over time</strong>
                            </p>
                        </blockquote>
                        <p>
                            Instead of reading one article at a time, you’re seeing:
                        </p>
                        <ul>
                            <li>patterns</li>
                            <li>tone</li>
                            <li>framing</li>
                            <li>evolution</li>
                        </ul>
                    </section>

                    <hr class="my-5">

                    <section class="mb-5">
                        <h4 class="fw-bold mb-3">🔍 What You’re Looking At</h4>
                        <h5>
                            😊 Sentiment
                        </h5>
                        <p>
                            How positive, neutral, or negative the coverage is.
                        </p>
                        <ul>
                            <li>🙂 Positive → optimistic tone</li>
                            <li>😐 Neutral → factual / balanced</li>
                            <li>☹️ Negative → critical or concerning</li>
                            <li>🤷 Mixed → unclear or conflicting</li>
                        </ul>

                        <hr class="my-5">

                        <h5>
                            🎭 Emotions
                        </h5>
                        <p>
                            The dominant emotional signals in the coverage.
                        </p>
                        <p>
                            Examples:
                        </p>
                        <ul>
                            <li>fear</li>
                            <li>anger</li>
                            <li>optimism</li>
                            <li>surprise</li>
                        </ul>
                        <p>
                            👉 This helps you feel the emotional direction of a story.
                        </p>

                        <hr class="my-5">

                        <h5>
                            🧩 Narrative Frames
                        </h5>
                        <p>
                            The <strong>angle</strong> the story is being told from.
                        </p>
                        <p>
                            Same event, different frames:
                        </p>
                        <ul>
                            <li>“economic impact”</li>
                            <li>“public safety”</li>
                            <li>“political strategy”</li>
                        </ul>
                        <p>
                            👉 This is where bias and perspective show up.
                        </p>

                        <hr class="my-5">

                        <h5>
                            🏷️ Entities
                        </h5>
                        <p>
                            The key people, companies, and places in the story.
                        </p>
                        <p>
                            Clicking an entity lets you:
                        </p>
                        <ul>
                            <li>track coverage across articles</li>
                            <li>see how sentiment changes over time</li>
                        </ul>

                    </section>

                    <hr class="my-5">

                    <section class="mb-5">
                        <h4 class="fw-bold mb-3">⏱️ Time Matters</h4>
                        <p>
                            Every analysis runs across a <strong>time window</strong> (like 24h, 7d, 30d).
                        </p>
                        <p>
                            This lets you see:
                        </p>
                        <ul>
                            <li>how a story evolves</li>
                            <li>how tone shifts</li>
                            <li>how narratives change</li>
                        </ul>
                        <p>
                            👉 News isn’t static—Scroll News lets you see movement.
                        </p>
                    </section>

                    <hr class="my-5">

                    <section class="mb-5">
                        <h4 class="fw-bold mb-3">⚡ How to Use Scroll News (Quick Flow)</h4>
                        <ol>
                            <li>Open any article</li>
                            <li>Scan sentiment + emotions</li>
                            <li>Look at narrative frames</li>
                            <li>Click an entity</li>
                            <li>Switch the time window</li>
                        </ol>
                        <p>
                            That’s it.
                        </p>
                        <p>
                            You’ve just gone from:
                        </p>
                        <blockquote>
                            <p>
                                reading news → analyzing coverage
                            </p>
                        </blockquote>
                    </section>

                    <hr class="my-5">

                    <section class="mb-5">
                        <h4 class="fw-bold mb-3">🧠 What This Unlocks</h4>
                        <p>
                            Use Scroll News to:
                        </p>
                        <ul>
                            <li>spot bias across sources</li>
                            <li>track how stories develop</li>
                            <li>compare emotional tone</li>
                            <li>understand <em>why</em> coverage feels a certain way</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h4 class="fw-bold mb-3">🚀 Start Exploring</h4>
                        <p>
                            Pick any article and try:
                        </p>
                        <ul>
                            <li>switching timeframes</li>
                            <li>clicking an entity</li>
                            <li>comparing frames</li>
                        </ul>
                        <p>
                            It only takes a minute to see the difference.
                        </p>
                    </section>

                </div>
            </div>
        </div>

        <!-- Footer-->
        <?php require_once BASE_PATH . '/views/partials/___footer.php'; ?>

        <!-- Modals-->
        <?php require_once BASE_PATH . '/views/partials/___modals.php'; ?>

        <!-- Bootstrap core JS-->
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js"></script>

    </body>
</html>