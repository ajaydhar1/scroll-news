<?php
/**
 * Coverage Timelines view helpers. Every card shows confirmed article data only:
 * headline, publisher, timestamp, publisher-supplied description and link.
 */

function sn_tl_e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function sn_tl_render_card(array $a, array $story): void
{
    $url    = $a['url'];
    $title  = $a['title'];
    $host   = $a['host'];
    $df     = function_exists('sn_domain_and_favicon') ? sn_domain_and_favicon($url) : ['favicon_url' => null];
    $fav    = $df['favicon_url'] ?? null;
    $profile = function_exists('sn_publisher_profile_url') && $host !== '' ? sn_publisher_profile_url($host) : '';
    $showImg = !empty($story['hero_image']) && $a['image'] !== '' && preg_match('#^https?://#i', $a['image']);
    $desc   = $a['description'];
    if (mb_strlen($desc) > 220) {
        $desc = rtrim(mb_substr($desc, 0, 217)) . '…';
    }
    $analyze = '/newsroom.php?url=' . urlencode($url) . '&category=' . urlencode(ucfirst($a['category'])) . '&pub_date=' . $a['dt']->getTimestamp() . '&db=1';
    $isRelated = $a['tier'] === 'related';
    ?>
    <li class="sn-tl-card-wrap<?= $isRelated ? ' is-related' : '' ?>" data-host="<?= sn_tl_e($host) ?>" data-tier="<?= sn_tl_e($a['tier']) ?>">
        <article class="sn-tl-card" id="a-<?= (int)$a['id'] ?>">
            <?php if ($showImg): ?>
                <div class="sn-tl-card-media">
                    <img src="<?= sn_tl_e($a['image']) ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"
                         onerror="this.closest('.sn-tl-card-media').style.display='none';">
                </div>
            <?php endif; ?>
            <div class="sn-tl-card-body">
                <div class="sn-tl-card-meta">
                    <?php if ($profile): ?>
                        <a class="sn-tl-pub" href="<?= sn_tl_e($profile) ?>">
                    <?php else: ?>
                        <span class="sn-tl-pub">
                    <?php endif; ?>
                        <?php if ($fav): ?><img src="<?= sn_tl_e($fav) ?>" alt="" width="14" height="14" loading="lazy" onerror="this.style.display='none';"><?php endif; ?>
                        <?= sn_tl_e($host) ?>
                    <?= $profile ? '</a>' : '</span>' ?>
                    <time datetime="<?= sn_tl_e($a['iso']) ?>"><?= sn_tl_e($a['dt']->format('g:i A T')) ?></time>
                    <?php if ($isRelated): ?><span class="sn-tl-badge" title="Mentions this story but is not primarily about it">Related</span><?php endif; ?>
                </div>
                <h3 class="sn-tl-card-title">
                    <a class="link-read article-link" href="<?= sn_tl_e($url) ?>" target="_blank" rel="noopener noreferrer"
                       data-article-url="<?= sn_tl_e($url) ?>"
                       data-article-title="<?= sn_tl_e($title) ?>"
                       data-article-source="<?= sn_tl_e($a['category']) ?>"
                       data-article-image="<?= sn_tl_e($a['image']) ?>"
                       data-article-pub-date="<?= sn_tl_e($a['iso']) ?>"
                       data-article-kind="external"><?= sn_tl_e($title) ?></a>
                </h3>
                <?php if ($desc !== ''): ?><p class="sn-tl-card-desc"><?= sn_tl_e($desc) ?></p><?php endif; ?>
                <div class="sn-tl-card-actions">
                    <a class="sn-tl-read link-read article-link" href="<?= sn_tl_e($url) ?>" target="_blank" rel="noopener noreferrer"
                       data-article-url="<?= sn_tl_e($url) ?>"
                       data-article-title="<?= sn_tl_e($title) ?>"
                       data-article-source="<?= sn_tl_e($a['category']) ?>"
                       data-article-image="<?= sn_tl_e($a['image']) ?>"
                       data-article-pub-date="<?= sn_tl_e($a['iso']) ?>"
                       data-article-kind="external"><span>Read story</span> <span aria-hidden="true">↗</span></a>
                    <a class="sn-tl-analyze" href="<?= sn_tl_e($analyze) ?>" data-loading>Analyze</a>
                </div>
            </div>
        </article>
    </li>
    <?php
}

/** Inline SVG volume bars. $link adds anchors to day sections. */
function sn_tl_render_sparkline(array $series, array $opts = []): void
{
    if (!$series) {
        return;
    }
    $link = !empty($opts['link']);
    $h = (int)($opts['height'] ?? 44);
    $max = 1;
    foreach ($series as $p) {
        $max = max($max, $p['core']);
    }
    $n = count($series);
    $bw = 10;
    $gap = 3;
    $w = $n * ($bw + $gap);
    ?>
    <svg class="sn-tl-spark" viewBox="0 0 <?= $w ?> <?= $h ?>" preserveAspectRatio="none" role="img"
         aria-label="Core coverage volume by day, <?= $n ?> days">
        <?php foreach ($series as $i => $p):
            $bh = $p['core'] > 0 ? max(3, (int)round($p['core'] / $max * ($h - 2))) : 1;
            $x = $i * ($bw + $gap);
            $rect = '<rect class="sn-tl-bar' . ($p['core'] ? '' : ' is-empty') . '" x="' . $x . '" y="' . ($h - $bh) . '" width="' . $bw . '" height="' . $bh . '" rx="2"></rect>';
            $label = sn_tl_fmt_day($p['date'], 'M j') . ': ' . $p['core'] . ' article' . ($p['core'] === 1 ? '' : 's');
            if ($link && $p['core'] > 0): ?>
                <a href="#day-<?= sn_tl_e($p['date']) ?>" data-day="<?= sn_tl_e($p['date']) ?>"><title><?= sn_tl_e($label) ?></title><?= $rect ?></a>
            <?php else: ?>
                <g><title><?= sn_tl_e($label) ?></title><?= $rect ?></g>
            <?php endif;
        endforeach; ?>
    </svg>
    <?php
}
