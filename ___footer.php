<?php

require_once('___modules.php');
require_once('OpenGraph.php');

// Choose a random article from your RSS feeds
require_once('Feed.php');

$random_article = getRandomArticle();

?>

        <footer class="footer footer-bottom py-4">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-4 text-lg-left">Copyright © Scroll News <?= date("Y") ?></div>
                    <div class="col-lg-4 my-3 my-lg-0">
                        <a class="btn btn-black btn-social mx-2" href="about.html"><i class="fas fa-align-right"></i></a>
                        <a class="btn btn-green btn-social mx-2" href="index.php?url=<?= urlencode($random_article['link']) ?>&category=<?= $random_article['category'] ?>"><i class="fas fa-play"></i></a>
                        <a class="btn btn-black btn-social mx-2" href="control-room.html"><i class="fas fa-align-left"></i></a>
                    </div>
                    <div class="col-lg-4 text-lg-right font-weight-bold">
                        <a href="index.php?url=<?= urlencode($random_article['link']) ?>&category=<?= $random_article['category'] ?>">scroll news</a>
                        <br>
                        <a href="terms.html" class="text-muted small mr-3">Terms</a>
                        <a href="privacy.html" class="text-muted small">Privacy</a>
                    </div>
                </div>
            </div>
        </footer>
