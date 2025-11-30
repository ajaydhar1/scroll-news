<?php
// inc/home-article-grid.php
// Expects $latest_articles = sn_get_latest_articles(...);
// and a helper time_ago() if you have one; otherwise just echo the date.

if (empty($latest_articles)) {
    return;
}
?>

<section class="sn-latest-grid my-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Latest in the News</h2>
            <a href="/newsroom.php" class="small text-muted">View all &raquo;</a>
        </div>

        <div class="row">
            <?php foreach ($latest_articles as $article): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <a href="<?= htmlspecialchars($article['url']) ?>"
                       class="text-decoration-none text-reset"
                       target="_blank" rel="noopener">
                        <div class="card h-100 shadow-sm border-0">

                            <?php if (!empty($article['screenshot_bytes'])): ?>
                                <!-- Adjust screenshot.php path/name if your endpoint differs -->
                                <img src="/screenshot.php?id=<?= (int)$article['id'] ?>"
                                     class="card-img-top"
                                     alt="">
                            <?php endif; ?>

                            <div class="card-body">
                                <div class="small text-muted">
                                    <?php if (!empty($article['created_at'])): ?>
                                        <?php if (function_exists('time_ago')): ?>
                                            <?= htmlspecialchars(time_ago($article['created_at'])) ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars(date('M j, Y g:ia', (int)$article['created_at'])) ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        &nbsp;
                                    <?php endif; ?>
                                </div>

                                <!-- If you later add a title column to the SELECT, drop it in here -->
                                <!--
                                <h3 class="card-title h6 mb-0">
                                    <?= htmlspecialchars($article['title']) ?>
                                </h3>
                                -->
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>