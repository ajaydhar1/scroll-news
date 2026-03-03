<?php
// inc/home-headlines-grid.php
// Expects: $articles = [...]; array of associative arrays with keys:
//   title, url, image_url (optional), source (optional), published_at (optional)

if (empty($articles)) {
    return;
}

// Pull out lead + secondary + rest
$lead = array_shift($articles);               // first article
$secondary = array_splice($articles, 0, 2);   // next two
$rest = $articles;

/**
 * Format a timestamp or date string into something readable.
 * You can replace this with your own time_ago() helper if you prefer.
 */
function sn_format_pub_time($value): string {
    if (!$value) return '';

    // If it's numeric, treat as Unix timestamp
    if (is_numeric($value)) {
        return date('M j, Y g:ia', (int) $value);
    }

    // If it's a string, try strtotime()
    $ts = strtotime($value);
    if ($ts !== false) {
        return date('M j, Y g:ia', $ts);
    }

    return (string) $value;
}
?>

<section class="sn-home-headlines my-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">In the News Right Now</h2>
            <a href="/newsroom.php" class="small text-muted">Browse all stories &raquo;</a>
        </div>

        <div class="row">
            <!-- Lead story -->
            <div class="col-12 col-lg-6 mb-4">
                <a href="<?= htmlspecialchars($lead['url']) ?>"
                   class="text-decoration-none text-reset"
                   target="_blank" rel="noopener">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if (!empty($lead['image_url'])): ?>
                            <img src="<?= htmlspecialchars($lead['image_url']) ?>"
                                 class="card-img-top"
                                 alt="<?= htmlspecialchars($lead['title']) ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="small text-muted mb-1">
                                <?php if (!empty($lead['source'])): ?>
                                    <?= htmlspecialchars($lead['source']) ?>
                                <?php endif; ?>
                                <?php if (!empty($lead['published_at'])): ?>
                                    &nbsp;&bull;&nbsp;
                                    <?= htmlspecialchars(sn_format_pub_time($lead['published_at'])) ?>
                                <?php endif; ?>
                            </div>
                            <h3 class="card-title h5 mb-2">
                                <?= htmlspecialchars($lead['title']) ?>
                            </h3>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Secondary stories -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="row">
                    <?php foreach ($secondary as $article): ?>
                        <div class="col-12 mb-3">
                            <a href="<?= htmlspecialchars($article['url']) ?>"
                               class="text-decoration-none text-reset"
                               target="_blank" rel="noopener">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="row g-0">
                                        <?php if (!empty($article['image_url'])): ?>
                                            <div class="col-4">
                                                <img src="<?= htmlspecialchars($article['image_url']) ?>"
                                                     class="img-fluid rounded-start h-100 w-100 object-fit-cover"
                                                     alt="<?= htmlspecialchars($article['title']) ?>">
                                            </div>
                                            <div class="col-8">
                                        <?php else: ?>
                                            <div class="col-12">
                                        <?php endif; ?>
                                                <div class="card-body py-2">
                                                    <div class="small text-muted mb-1">
                                                        <?php if (!empty($article['source'])): ?>
                                                            <?= htmlspecialchars($article['source']) ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($article['published_at'])): ?>
                                                            &nbsp;&bull;&nbsp;
                                                            <?= htmlspecialchars(sn_format_pub_time($article['published_at'])) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <h4 class="card-title h6 mb-0">
                                                        <?= htmlspecialchars($article['title']) ?>
                                                    </h4>
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($rest)): ?>
            <!-- Remaining stories in a grid -->
            <div class="row mt-2">
                <?php foreach ($rest as $article): ?>
                    <div class="col-12 col-md-6 col-lg-3 mb-4">
                        <a href="<?= htmlspecialchars($article['url']) ?>"
                           class="text-decoration-none text-reset"
                           target="_blank" rel="noopener">
                            <div class="card h-100 shadow-sm border-0">
                                <?php if (!empty($article['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($article['image_url']) ?>"
                                         class="card-img-top"
                                         alt="<?= htmlspecialchars($article['title']) ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="small text-muted mb-1">
                                        <?php if (!empty($article['source'])): ?>
                                            <?= htmlspecialchars($article['source']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($article['published_at'])): ?>
                                            &nbsp;&bull;&nbsp;
                                            <?= htmlspecialchars(sn_format_pub_time($article['published_at'])) ?>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="card-title h6 mb-0">
                                        <?= htmlspecialchars($article['title']) ?>
                                    </h5>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
