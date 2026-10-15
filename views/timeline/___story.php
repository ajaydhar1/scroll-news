<?php
/** Single story timeline. Expects $tl_story and $tl_model (may be null on DB failure). */
$story = $tl_story;
$m = $tl_model;
$s = $m['stats'] ?? null;
$slug = $story['slug'];
$maxStack = 8;
$visibleStack = 6;
?>
<section class="sn-tl-story sn-tl-theme-<?= sn_tl_e($slug) ?>">
    <a class="sn-tl-back" href="/timeline" data-loading><span aria-hidden="true">←</span> All timelines</a>

    <header class="sn-tl-hero">
        <p class="sn-tl-eyebrow"><?= sn_tl_e($story['kicker']) ?>
            <span class="sn-tl-status sn-tl-status-<?= sn_tl_e($story['status']) ?>"><?= $story['status'] === 'active' ? 'Developing' : 'Archived' ?></span>
        </p>
        <h1 class="sn-tl-title"><?= sn_tl_e($story['title']) ?></h1>
        <p class="sn-tl-dek"><span class="sn-tl-tag sn-tl-tag-editorial">Editor’s note</span> <?= sn_tl_e($story['dek']) ?></p>

        <?php if ($s && $s['core_articles'] > 0): ?>
            <dl class="sn-tl-stats" aria-label="Coverage statistics computed from the archive">
                <div><dt>Articles</dt><dd><?= (int)$s['core_articles'] ?></dd></div>
                <div><dt>Publishers</dt><dd><?= (int)$s['publishers'] ?></dd></div>
                <div><dt>Days with coverage</dt><dd><?= (int)$s['days_active'] ?></dd></div>
                <div><dt>Busiest day</dt><dd><?= sn_tl_e(sn_tl_fmt_day($s['peak_day'], 'M j')) ?> <small>(<?= (int)$s['peak_count'] ?>)</small></dd></div>
            </dl>
            <p class="sn-tl-tag-line"><span class="sn-tl-tag sn-tl-tag-data">From the data</span> Counts are computed from archived articles in this timeline, not editorial judgement.</p>
        <?php endif; ?>
    </header>

    <?php if (!$m || !$s || $s['core_articles'] === 0): ?>
        <div class="sn-tl-notice" role="status">This timeline isn’t available right now. Please try again shortly.</div>
    <?php else: ?>

    <div class="sn-tl-shell">
        <input type="checkbox" id="snTlRelated" class="sn-tl-related-toggle"<?= !empty($_GET['related']) ? ' checked' : '' ?>>

        <div class="sn-tl-controls">
            <div class="sn-tl-scrubber" aria-label="Coverage volume. Select a bar to jump to that day.">
                <?php sn_tl_render_sparkline($m['series'], ['link' => true, 'height' => 40]); ?>
                <div class="sn-tl-scrubber-axis">
                    <span><?= sn_tl_e(sn_tl_fmt_day($s['first_day'], 'M j')) ?></span>
                    <span class="sn-tl-scrubber-cap">Articles per day</span>
                    <span><?= sn_tl_e(sn_tl_fmt_day($s['last_day'], 'M j')) ?></span>
                </div>
            </div>
            <div class="sn-tl-filters">
                <label class="sn-tl-switch" for="snTlRelated">
                    <span class="sn-tl-switch-track" aria-hidden="true"></span>
                    <span>Include related coverage <small>(<?= (int)$s['related_articles'] ?>)</small></span>
                </label>
                <label class="sn-tl-pubfilter" hidden>
                    <span class="sn-visually-hidden">Filter by publisher</span>
                    <select id="snTlPublisher" aria-label="Filter by publisher">
                        <option value="">All publishers</option>
                        <?php
                        $allPubs = [];
                        foreach ($m['days'] as $d) {
                            foreach ($d['articles'] as $a) {
                                $allPubs[$a['host']] = ($allPubs[$a['host']] ?? 0) + 1;
                            }
                        }
                        arsort($allPubs);
                        foreach ($allPubs as $h => $n): ?>
                            <option value="<?= sn_tl_e($h) ?>"><?= sn_tl_e($h) ?> (<?= (int)$n ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </div>

        <div class="sn-tl-layout">
            <aside class="sn-tl-minimap" aria-label="Timeline outline">
                <p class="sn-tl-minimap-title">Chapters</p>
                <ol>
                    <?php foreach ($m['chapters'] as $i => $ch): if (!$ch['first_day']) continue; ?>
                        <li><a href="#chapter-<?= (int)$i ?>" data-chapter="<?= (int)$i ?>">
                            <span class="sn-tl-minimap-dot" aria-hidden="true"></span>
                            <span class="sn-tl-minimap-text">
                                <strong><?= sn_tl_e($ch['title']) ?></strong>
                                <small><?= sn_tl_e(sn_tl_fmt_day($ch['first_day'], 'M j')) ?> · <?= (int)$ch['core_articles'] ?> articles</small>
                            </span>
                        </a></li>
                    <?php endforeach; ?>
                </ol>
            </aside>

            <div class="sn-tl-rail-wrap">
                <?php
                // Chapter markers attach to the first displayed core day of each chapter
                $chapterAt = [];
                foreach ($m['chapters'] as $i => $ch) {
                    if ($ch['first_day']) {
                        $chapterAt[$ch['first_day']] = $i;
                    }
                }
                ?>
                <ol class="sn-tl-rail">
                    <?php foreach ($m['days'] as $date => $day):
                        $coreOnly = $day['core'] > 0;
                        if (isset($chapterAt[$date])):
                            $ci = $chapterAt[$date];
                            $ch = $m['chapters'][$ci]; ?>
                            <li class="sn-tl-chapter" id="chapter-<?= $ci ?>">
                                <div class="sn-tl-chapter-inner">
                                    <p class="sn-tl-chapter-label"><span class="sn-tl-tag sn-tl-tag-editorial">Editor’s note</span> Chapter <?= $ci + 1 ?> · <?= sn_tl_e(sn_tl_fmt_day($ch['from'], 'M j')) ?> – <?= sn_tl_e(sn_tl_fmt_day($ch['to'], 'M j')) ?></p>
                                    <h2><?= sn_tl_e($ch['title']) ?></h2>
                                    <p><?= sn_tl_e($ch['summary']) ?></p>
                                    <?php if (!empty($ch['basis'])): ?>
                                        <p class="sn-tl-basis">Based on:
                                        <?php foreach ($ch['basis'] as $k => $bid): ?><a href="#a-<?= (int)$bid ?>">article <?= $k + 1 ?></a><?= $k < count($ch['basis']) - 1 ? ', ' : '' ?><?php endforeach; ?></p>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endif;
                        if ($coreOnly && isset($m['gaps'][$date])): $g = $m['gaps'][$date]; ?>
                            <li class="sn-tl-gap" aria-label="Gap in coverage">
                                <span>No high-confidence coverage for <?= (int)$g['days'] ?> day<?= $g['days'] === 1 ? '' : 's' ?></span>
                            </li>
                        <?php endif; ?>
                        <li class="sn-tl-day<?= $coreOnly ? '' : ' is-related-only' ?>" id="day-<?= sn_tl_e($date) ?>" data-day="<?= sn_tl_e($date) ?>">
                            <div class="sn-tl-day-head">
                                <time datetime="<?= sn_tl_e($date) ?>"><?= sn_tl_e(sn_tl_fmt_day($date, 'l, F j')) ?></time>
                                <span class="sn-tl-day-count"><?= (int)$day['core'] ?> article<?= $day['core'] === 1 ? '' : 's' ?><?= $day['related'] ? ' <span class="sn-tl-day-rel">+ ' . (int)$day['related'] . ' related</span>' : '' ?></span>
                            </div>
                            <?php
                            $arts = $day['articles'];
                            $splitAt = null;
                            if ($day['core'] > $maxStack) {
                                $seen = 0;
                                foreach ($arts as $ix => $a) {
                                    if ($a['tier'] === 'core') {
                                        $seen++;
                                    }
                                    if ($seen === $visibleStack) {
                                        $splitAt = $ix + 1;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <ol class="sn-tl-cards">
                                <?php foreach ($arts as $ix => $a):
                                    if ($splitAt !== null && $ix === $splitAt): ?>
                                        <li class="sn-tl-more-wrap"><details class="sn-tl-more"><summary>Show <?= $day['core'] - $visibleStack ?> more</summary>
                                        <ol class="sn-tl-cards sn-tl-cards-inner">
                                    <?php endif;
                                    sn_tl_render_card($a, $story);
                                endforeach;
                                if ($splitAt !== null): ?>
                                        </ol></details></li>
                                <?php endif; ?>
                            </ol>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <p class="sn-tl-end">End of timeline</p>
            </div>
        </div>

        <details class="sn-tl-method">
            <summary>How this timeline is built</summary>
            <div class="sn-tl-method-body">
                <p><span class="sn-tl-tag sn-tl-tag-confirmed">Published</span> Headlines, publishers, times and links come directly from archived articles. Times are shown in Eastern Time.</p>
                <p><span class="sn-tl-tag sn-tl-tag-data">From the data</span> An article is included when its headline names the subject, or its description plus Scroll News’ entity analysis points to it. Weaker matches are marked <em>Related</em> and hidden by default. Exact duplicate headlines are removed.</p>
                <p><span class="sn-tl-tag sn-tl-tag-editorial">Editor’s note</span> Chapter titles and summaries are written by editors, based on the articles linked under each chapter. They are interpretation, not article content. No development claims are generated automatically.</p>
                <p class="sn-tl-method-nums">
                    <?= (int)$s['core_articles'] ?> articles shown · <?= (int)$s['related_articles'] ?> related · <?= (int)$s['rejected'] ?> candidates rejected · <?= (int)$s['duplicates_removed'] ?> duplicate<?= $s['duplicates_removed'] === 1 ? '' : 's' ?> removed
                </p>
                <p>Publisher mix (top <?= count($s['publisher_mix']) ?>):
                    <?php $mix = []; foreach ($s['publisher_mix'] as $h => $n) { $mix[] = sn_tl_e($h) . ' ' . (int)$n; } echo implode(' · ', $mix); ?>
                    <?php if ($s['top_publisher_share'] >= 0.4): ?>
                        <br><em>Note: <?= sn_tl_e($s['top_publisher']) ?> accounts for <?= (int)round($s['top_publisher_share'] * 100) ?>% of this timeline, so it reflects that outlet’s coverage heavily.</em>
                    <?php endif; ?>
                </p>
                <?php foreach ($story['notes'] ?? [] as $note): ?><p class="sn-tl-note">• <?= sn_tl_e($note) ?></p><?php endforeach; ?>
            </div>
        </details>
    </div>
    <?php endif; ?>
</section>
