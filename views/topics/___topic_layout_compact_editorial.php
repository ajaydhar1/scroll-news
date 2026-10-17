<?php
/**
 * Compact Editorial layout preset.
 *
 * Expects: $topic (normalised config), $data (sn_topic_fetch result), $plan (sn_topic_plan_layout),
 * $page (int). Regions render only when they contain stories.
 */
require_once __DIR__ . '/___topic_article.php';

$ceItems = $data['items'];
$ceHasRail = !empty($plan['rail']);
$ceWindowLabel = sn_topic_window_label((int) $data['window_hours']);
$ceSearchUrl = '/search.php?' . http_build_query(['q' => $topic['query'], 'range' => 'all', 'mode' => 'classic']);
?>
<header class="sn-topic-head">
    <p class="sn-topic-eyebrow">Topic</p>
    <h1 class="sn-topic-title"><?= sn_topic_e($topic['name']) ?></h1>
    <?php if ($topic['dek'] !== ''): ?>
        <p class="sn-topic-dek"><?= sn_topic_e($topic['dek']) ?></p>
    <?php endif; ?>
    <?php if ($ceItems || $data['total'] > 0): ?>
        <p class="sn-topic-range">
            <span>Latest coverage &middot; <?= sn_topic_e($ceWindowLabel) ?></span>
            <?php if ($data['total'] > 0): ?>
                <span><?= number_format((int) $data['total']) ?> stories</span>
            <?php endif; ?>
            <?php if ($topic['timeline']): ?>
                <a href="/timeline.php?story=<?= rawurlencode($topic['timeline']) ?>" data-loading>Coverage timeline</a>
            <?php endif; ?>
            <a href="<?= sn_topic_e($ceSearchUrl) ?>" data-loading>Search all coverage</a>
        </p>
    <?php endif; ?>
    <?php if ($data['widened'] && $ceItems): ?>
        <p class="sn-topic-notice" role="note">
            Fewer than <?= (int) $topic['min_items'] ?> stories in the <?= sn_topic_e(sn_topic_window_label((int) $data['requested_hours'])) ?>,
            so this page covers the <?= sn_topic_e($ceWindowLabel) ?>.
        </p>
    <?php endif; ?>
</header>

<?php if ($data['error']): ?>
    <p class="sn-topic-empty">Topic coverage is temporarily unavailable. Please try again shortly, or <a href="<?= sn_topic_e($ceSearchUrl) ?>" data-loading>search for <?= sn_topic_e($topic['name']) ?></a>.</p>
<?php elseif (!$ceItems): ?>
    <p class="sn-topic-empty">No stories about <?= sn_topic_e($topic['name']) ?> in the <?= sn_topic_e($ceWindowLabel) ?>. Try <a href="<?= sn_topic_e($ceSearchUrl) ?>" data-loading>searching all coverage</a>.</p>
<?php else: ?>

    <?php if ($plan['lead']): ?>
        <section class="sn-topic-opening<?= $ceHasRail ? ' has-rail' : '' ?>" aria-label="Top stories">
            <div class="sn-topic-primary">
                <?php sn_topic_render_story($plan['lead'], 'lead', $plan['lead_mode'] === 'image', 'h2'); ?>

                <?php if ($plan['support']): ?>
                    <div class="sn-topic-support sn-topic-support--<?= count($plan['support']) ?>">
                        <?php foreach ($plan['support'] as $it): ?>
                            <?php sn_topic_render_story($it, 'support', true); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($ceHasRail): ?>
                <aside class="sn-topic-rail" aria-label="More recent stories">
                    <h2 class="sn-topic-label">Latest</h2>
                    <ol class="sn-tp-rail-list">
                        <?php foreach ($plan['rail'] as $it): ?>
                            <?php sn_topic_render_rail_item($it); ?>
                        <?php endforeach; ?>
                    </ol>
                </aside>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($plan['stream']): ?>
        <section class="sn-topic-stream" aria-label="More coverage">
            <h2 class="sn-topic-label"><?= $page > 1 ? 'More coverage' : 'More on ' . sn_topic_e($topic['name']) ?></h2>
            <?php foreach ($plan['stream'] as $block): ?>
                <div class="sn-topic-block sn-topic-block--<?= sn_topic_e($block['type']) ?>">
                    <?php foreach ($block['items'] as $it): ?>
                        <?php
                        if ($block['type'] === 'wide') {
                            sn_topic_render_story($it, 'wide', true);
                        } elseif ($block['type'] === 'pair') {
                            sn_topic_render_story($it, 'card', true);
                        } else {
                            sn_topic_render_story($it, 'brief', false);
                        }
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($page > 1 || $data['has_more']): ?>
        <nav class="sn-topic-pager" aria-label="Topic pages">
            <?php if ($page > 1): ?>
                <a href="<?= sn_topic_e(sn_topic_url($topic['slug'], $page > 2 ? ['page' => $page - 1, 'w' => $data['window_hours']] : ['w' => $data['window_hours']])) ?>" rel="prev" data-loading>&larr; Newer stories</a>
            <?php endif; ?>
            <?php if ($data['has_more']): ?>
                <a href="<?= sn_topic_e(sn_topic_url($topic['slug'], ['page' => $page + 1, 'w' => $data['window_hours']])) ?>" rel="next" data-loading>Older stories &rarr;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

<?php endif; ?>
