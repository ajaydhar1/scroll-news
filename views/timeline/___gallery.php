<?php
/** Gallery of curated timelines. Expects $tl_models (slug => model|null). */
?>
<section class="sn-tl-gallery">
    <header class="sn-tl-gallery-head">
        <p class="sn-tl-eyebrow">Coverage Timelines</p>
        <h1 class="sn-tl-title">See how a story unfolded</h1>
        <p class="sn-tl-lede">Hand-curated timelines of major developing stories, built from the articles Scroll News has archived. Every card is real reporting with its publisher and timestamp; the chapter notes are labelled editorial.</p>
    </header>

    <?php if (!empty($tl_not_found)): ?>
        <div class="sn-tl-notice" role="status">We couldn’t find that timeline. Here are the ones we have.</div>
    <?php endif; ?>

    <div class="sn-tl-gallery-grid">
        <?php foreach (sn_tl_all_stories() as $slug => $story):
            $m = $tl_models[$slug] ?? null;
            $s = $m['stats'] ?? null; ?>
            <a class="sn-tl-gcard sn-tl-theme-<?= sn_tl_e($slug) ?>" href="/timeline.php?story=<?= sn_tl_e($slug) ?>" data-loading>
                <span class="sn-tl-gcard-kicker"><?= sn_tl_e($story['kicker']) ?>
                    <span class="sn-tl-status sn-tl-status-<?= sn_tl_e($story['status']) ?>"><?= $story['status'] === 'active' ? 'Developing' : 'Archived' ?></span>
                </span>
                <span class="sn-tl-gcard-title"><?= sn_tl_e($story['title']) ?></span>
                <span class="sn-tl-gcard-dek"><?= sn_tl_e($story['dek']) ?></span>
                <?php if ($m && $s && $s['core_articles'] > 0): ?>
                    <span class="sn-tl-gcard-spark"><?php sn_tl_render_sparkline($m['series'], ['height' => 36]); ?></span>
                    <span class="sn-tl-gcard-stats">
                        <span><strong><?= (int)$s['core_articles'] ?></strong> articles</span>
                        <span><strong><?= (int)$s['publishers'] ?></strong> publishers</span>
                        <span><?= sn_tl_e(sn_tl_fmt_day($s['first_day'], 'M j')) ?> – <?= sn_tl_e(sn_tl_fmt_day($s['last_day'], 'M j')) ?></span>
                    </span>
                <?php else: ?>
                    <span class="sn-tl-gcard-stats"><span>Coverage data unavailable right now</span></span>
                <?php endif; ?>
                <span class="sn-tl-gcard-cta">Open timeline <span aria-hidden="true">→</span></span>
            </a>
        <?php endforeach; ?>
    </div>

    <p class="sn-tl-fineprint">More timelines are added by the editorial team. Coverage reflects the Scroll News archive, which began on Dec 1, 2025.</p>
</section>
