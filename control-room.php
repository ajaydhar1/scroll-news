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
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:400,100,300,700&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&family=Open+Sans&display=swap" rel="stylesheet" />

        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css?v=<?php echo filemtime(__DIR__ . '/css/styles.css'); ?>" rel="stylesheet" />
        <link href="css/custom.css?v=<?php echo filemtime(__DIR__ . '/css/custom.css'); ?>" rel="stylesheet" />
        <link href="css/control-room.css?v=<?php echo filemtime(__DIR__ . '/css/control-room.css'); ?>" rel="stylesheet" />

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
                
                <!-- Footer-->        
                <?php require_once __DIR__ . '/___footer_control_room.php'; ?>

            </div>
        </div>

        <!-- Modals-->        
        <?php require_once __DIR__ . '/___modals.php'; ?>

        <!-- Core JS (Bootstrap 4 requires jQuery first) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" defer></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.bundle.min.js" defer></script>

    </body>
</html>
