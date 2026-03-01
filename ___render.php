<?php
/**
 * Normalize pub_date into:
 * - unix timestamp (int|null)
 * - ISO string (DATE_ATOM) (string)
 * - human formatted string using sn_format_pub_date() (string)
 */
function sn_pubdate_normalize($pubRaw): array {
    $ts  = null;
    $iso = '';

    if (is_numeric($pubRaw)) {
        $ts  = (int)$pubRaw;
        $iso = gmdate(DATE_ATOM, $ts);
    } elseif (is_string($pubRaw) && $pubRaw !== '') {
        $tmp = strtotime($pubRaw);
        if ($tmp !== false) {
            $ts  = (int)$tmp;
            $iso = gmdate(DATE_ATOM, $ts);
        }
    }

    $human = sn_format_pub_date($pubRaw ?? null);

    return ['ts' => $ts, 'iso' => $iso, 'human' => $human];
}

/**
 * Build favicon + domain from read URL.
 */
function sn_domain_and_favicon(?string $readUrl): array {
    $domain = '';
    $faviconUrl = null;

    if ($readUrl) {
        $host = parse_url($readUrl, PHP_URL_HOST);
        if ($host) {
            $domain = preg_replace('/^www\./i', '', $host);

            $faviconUrl = 'https://t0.gstatic.com/faviconV2'
                . '?client=SOCIAL&type=FAVICON'
                . '&fallback_opts=TYPE,SIZE,URL'
                . '&url=' . rawurlencode($readUrl)
                . '&size=64';
        }
    }

    return ['domain' => $domain, 'favicon_url' => $faviconUrl];
}

/**
 * Convert a result row (classic or NLP) into a consistent view model for rendering.
 *
 * $ctx keys:
 *  - mode: 'classic'|'nlp'
 *  - analysis_window: default '7d' (used by some links)
 *  - category_url_builder: callable($feedNameLower, $window) => string
 *  - badge_href_builder: callable($badgeSlug, $vm, $opts) => string
 */
function sn_article_vm_from_row(array $row, array $ctx = []): array {
    $mode = ($ctx['mode'] ?? 'classic') === 'nlp' ? 'nlp' : 'classic';
    $isNlpMode = ($mode === 'nlp');

    // --- Map fields by mode ---
    $title   = (string)($row['title'] ?? '');
    $readUrl = $isNlpMode ? (string)($row['url'] ?? '#') : (string)($row['link'] ?? '#');

    $feedNameRaw   = $isNlpMode ? (string)($row['source_slug'] ?? '') : (string)($row['feed_name'] ?? '');
    $feedNameHuman = $feedNameRaw !== '' ? ucfirst($feedNameRaw) : '';
    $feedNameLower = strtolower($feedNameRaw);

    // --- Dates ---
    // Search rows typically provide 'pub_date'
    // Archive rss_items rows typically provide '_ts' (int) and/or '_dt' (DateTimeInterface)
    $hasPubDateKey = array_key_exists('pub_date', $row);

    // Prefer pub_date if present; otherwise fall back to archive keys.
    $pubRaw = $row['pub_date'] ?? null;
    if (!$hasPubDateKey || $pubRaw === null || $pubRaw === '') {
        if (isset($row['_ts']) && $row['_ts'] !== null && $row['_ts'] !== '') {
            $pubRaw = $row['_ts'];
        } elseif (($row['_dt'] ?? null) instanceof DateTimeInterface) {
            // Use ISO string so normalize() can safely parse it
            $pubRaw = $row['_dt']->format(DATE_ATOM);
        }
    }

    $pd = sn_pubdate_normalize($pubRaw);

    $pubHuman = $pd['human'];
    $pubIso   = $pd['iso'];
    $pubTs    = $pd['ts'];

    // If this is an archive-style row (no pub_date provided) AND we have a DateTime,
    // use the nicer time-only label (matches your existing archive display).
    if (!$hasPubDateKey && (($row['_dt'] ?? null) instanceof DateTimeInterface)) {
        $pubHuman = $row['_dt']->format('g:i A');
    }

    // --- Analyze availability ---
    $forceAnalyze = !empty($ctx['force_analyze']);

    // We only *know* it's in the articles table when article_id is present.
    $inArticlesTable = !empty($row['article_id']);

    // Show Analyze button:
    // - NLP mode: always
    // - Classic: if forced (archive) OR if it has article_id (search classic)
    $canAnalyze = $isNlpMode ? true : ($forceAnalyze || $inArticlesTable);

    // In NLP mode, always analyzable.
    // In classic mode, analyzable if explicitly forced OR if it has article_id (search classic).
    $hasNlp = $isNlpMode ? true : ($forceAnalyze || !empty($row['article_id']));

    // --- Analyze URL (newsroom.php) ---
    $analyzeUrl = null;
    if ($canAnalyze && $readUrl && $readUrl !== '#') {
        $analyzeUrl = 'newsroom.php?url=' . urlencode($readUrl)
            . '&category=' . urlencode($feedNameHuman ?: $feedNameRaw)
            . '&pub_date=' . urlencode($pubTs);

        // Only add db=1 when we *know* it's in articles
        if ($inArticlesTable) {
            $analyzeUrl .= '&db=1';
        }
    }

    // --- Domain & favicon ---
    $df = sn_domain_and_favicon($readUrl);
    $domain = $df['domain'];
    $faviconUrl = $df['favicon_url'];

    // --- Badges & card classes ---
    $badges = scroll_get_article_badges($row);

    $cardClasses = ' scroll-history-card';
    if (scroll_is_high_signal_publisher($row)) {
        $cardClasses .= ' scroll-card-high-signal';
    }
    if (scroll_is_deep_dive($row)) {
        $cardClasses .= ' scroll-card-deep-dive';
    }

    // --- NLP detail blocks (only if row has nlp) ---
    $hashtags = [];
    $sentiment = ['label' => null, 'emoji' => '', 'percent' => null];
    $topEmotions = [];

    $nlpRaw = $row['nlp'] ?? null;

    $nlp = [];
    if (is_string($nlpRaw) && $nlpRaw !== '') {
        $nlp = json_decode($nlpRaw, true) ?: [];
    } elseif (is_array($nlpRaw)) {
        $nlp = $nlpRaw;
    }

    if (!empty($nlp)) {
        // Hashtags from keywords
        $keywords = $nlp['keywords'] ?? [];
        foreach ($keywords as $kw) {
            $kw = trim((string)$kw);
            if ($kw === '') continue;
            if ($kw[0] !== '#') $kw = '#' . $kw;
            $hashtags[] = $kw;
        }
        $hashtags = array_slice($hashtags, 0, 5);

        // Sentiment
        $label = $nlp['sentiment']['label'] ?? null;
        $score = $nlp['sentiment']['score'] ?? null;

        $emoji = '';
        if ($label === 'positive') $emoji = '😊';
        elseif ($label === 'negative') $emoji = '😔';
        elseif ($label === 'neutral')  $emoji = '😐';

        $percent = null;
        if (is_numeric($score)) $percent = (int)round(((float)$score) * 100);

        $sentiment = ['label' => $label, 'emoji' => $emoji, 'percent' => $percent];

        // Emotional reaction (top 3)
        $emotionsRaw = $nlp['emotional_reaction'] ?? [];
        $emotions = [];
        foreach ($emotionsRaw as $emoLabel => $value) {
            if (!is_numeric($value)) continue;
            $emotions[] = ['label' => (string)$emoLabel, 'value' => (float)$value];
        }
        usort($emotions, fn($a,$b) => $b['value'] <=> $a['value']);
        $topEmotions = array_slice($emotions, 0, 3);
    }

    // Default category URL builder
    $categoryUrl = null;
    if ($feedNameLower !== '') {
        if (isset($ctx['category_url_builder']) && is_callable($ctx['category_url_builder'])) {
            $categoryUrl = (string)call_user_func($ctx['category_url_builder'], $feedNameLower, $ctx['analysis_window'] ?? '7d');
        } else {
            $w = $ctx['analysis_window'] ?? '7d';
            $categoryUrl = "/analysis.php?context=category&value=" . urlencode($feedNameLower) . "&w=" . urlencode($w);
        }
    }

    return [
        'title' => $title,
        'read_url' => $readUrl,
        'pub_human' => $pubHuman,
        'pub_iso' => $pubIso,
        'pub_ts' => $pubTs,

        'feed_name_raw' => $feedNameRaw,
        'feed_name_human' => $feedNameHuman,
        'feed_name_lower' => $feedNameLower,
        'category_url' => $categoryUrl,

        'domain' => $domain,
        'favicon_url' => $faviconUrl,

        'has_nlp' => $canAnalyze,
        'analyze_url' => $analyzeUrl,

        'badges' => $badges,
        'card_classes' => $cardClasses,

        'hashtags' => $hashtags,
        'sentiment' => $sentiment,
        'top_emotions' => $topEmotions,

        // pass through for callers that need raw stuff
        'row' => $row,
    ];
}

/**
 * Render the card. Options let each page style/toggle sections differently.
 *
 * $opts keys:
 *  - card_class: string (page-specific class, e.g. "sn-search-card")
 *  - show_badges: bool (default true)
 *  - show_hashtags: bool (default true)
 *  - show_sentiment: bool (default true)
 *  - show_emotions: bool (default true)
 *  - show_analyze: bool (default true)
 *  - badge_href_builder: callable($badgeSlug, $vm, $opts) => string
 *  - analysis_window: '7d' etc (for tag links)
 */
function sn_render_article_card(array $vm, array $opts = []): void {
    $cardClass = (string)($opts['card_class'] ?? '');
    $showBadges    = $opts['show_badges']    ?? true;
    $showHashtags  = $opts['show_hashtags']  ?? true;
    $showSentiment = $opts['show_sentiment'] ?? true;
    $showEmotions  = $opts['show_emotions']  ?? true;
    $showAnalyze   = $opts['show_analyze']   ?? true;

    $title     = $vm['title'] ?? '';
    $readUrl   = $vm['read_url'] ?? '#';
    $pubHuman  = $vm['pub_human'] ?? '';
    $pubIso    = $vm['pub_iso'] ?? '';
    $feedHuman = $vm['feed_name_human'] ?? '';
    $feedLower = $vm['feed_name_lower'] ?? '';
    $categoryUrl = $vm['category_url'] ?? null;

    $domain    = $vm['domain'] ?? '';
    $faviconUrl = $vm['favicon_url'] ?? null;

    $badges = $vm['badges'] ?? [];
    $hashtags = $vm['hashtags'] ?? [];
    $sentiment = $vm['sentiment'] ?? ['label'=>null,'emoji'=>'','percent'=>null];
    $topEmotions = $vm['top_emotions'] ?? [];

    $hasNlp = !empty($vm['has_nlp']);
    $analyzeUrl = $vm['analyze_url'] ?? null;

    $row = $vm['row'] ?? [];

    $extraCardClasses = (string)($vm['card_classes'] ?? '');

    // Badge href builder (optional)
    $badgeHrefBuilder = $opts['badge_href_builder'] ?? null;

    ?>
    <div class="card mb-3 shadow-sm border-0 <?= htmlspecialchars($cardClass, ENT_QUOTES, 'UTF-8'); ?><?= htmlspecialchars($extraCardClasses, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="card-body">
            <h5 class="card-title mb-1">
                <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
            </h5>

            <div class="sn-search-meta small text-muted mb-3 d-flex align-items-center">
                <?php if ($faviconUrl): ?>
                    <a href="https://<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                        <img
                            src="<?= htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?= htmlspecialchars($domain ?: $feedHuman ?: 'Site', ENT_QUOTES, 'UTF-8'); ?> logo"
                            class="sn-favicon"
                        >
                    </a>
                <?php endif; ?>

                <div class="sn-meta-text">
                    <?php if ($pubHuman): ?>
                        <?= htmlspecialchars($pubHuman, ENT_QUOTES, 'UTF-8'); ?>
                    <?php endif; ?>

                    <?php if ($feedHuman): ?>
                        <?php if ($pubHuman): ?> • <?php endif; ?>

                        <?php if ($categoryUrl): ?>
                            <a href="<?= htmlspecialchars($categoryUrl, ENT_QUOTES, 'UTF-8'); ?>" class="sn-category-link" data-loading>
                                <?= htmlspecialchars($feedHuman, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        <?php else: ?>
                            <?= htmlspecialchars($feedHuman, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($domain): ?>
                        <?php if ($pubHuman || $feedHuman): ?> • <?php endif; ?>
                        <a href="https://<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            <?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($showBadges && !empty($badges)) : ?>
                <div class="scroll-article-badges">
                    <?php foreach ($badges as $badge): ?>
                        <?php
                        $slug = $badge['slug'] ?? '';
                        $tooltip = $badge['tooltip'] ?? '';
                        $label = $badge['label'] ?? '';

                        $href = '#';
                        if (is_callable($badgeHrefBuilder)) {
                            $href = (string)call_user_func($badgeHrefBuilder, $slug, $vm, $opts);
                        } else {
                            // sensible defaults (can be overridden by passing a builder)
                            if ($slug === 'deep-dive') $href = '/search.php?mode=nlp&deep_dive=1';
                            elseif ($slug === 'high-signal-publisher') $href = '/search.php?high_signal=1';
                            else $href = '/search.php';
                        }
                        ?>
                        <a class="scroll-badge scroll-badge-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>"
                           href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"
                           title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8'); ?>"
                           data-loading>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($showHashtags && !empty($hashtags)): ?>
                <div class="sn-hashtags mt-1">
                    <?php foreach ($hashtags as $tag): ?>
                        <?php
                        $raw = (string)$tag;
                        $clean = trim(ltrim($raw, "# \t\n\r\0\x0B"));
                        $w = $opts['analysis_window'] ?? '7d';
                        $href = sn_analysis_url($clean, $w, 'entity');
                        ?>
                        <a class="sn-hashtag-chip"
                           href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                           data-loading>
                            <?= htmlspecialchars($raw, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($showSentiment && !empty($sentiment['label'])): ?>
                <div class="sn-sentiment mt-1">
                    <span class="sn-sentiment-label">
                        <?php if (!empty($sentiment['emoji'])): ?>
                            <span class="mr-1"><?= htmlspecialchars($sentiment['emoji'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <?= htmlspecialchars(ucfirst((string)$sentiment['label']), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <?php if ($sentiment['percent'] !== null): ?>
                        <span class="sn-sentiment-score text-muted small">
                            (<?= (int)$sentiment['percent']; ?>%)
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($showEmotions && !empty($topEmotions)): ?>
                <div class="sn-emotions mt-1">
                    <?php foreach ($topEmotions as $emo): ?>
                        <div class="sn-emotion-bar">
                            <span class="sn-emotion-label"><?= htmlspecialchars($emo['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <div class="sn-emotion-bar-track">
                                <div class="sn-emotion-bar-fill"
                                     style="width: <?= (float)max(5, min(100, (float)($emo['value'] ?? 0))); ?>%;">
                                </div>
                            </div>
                            <span class="sn-emotion-value"><?= (int)round((float)($emo['value'] ?? 0)); ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="btn-group btn-group-sm<?php
                if (!empty($hashtags) || !empty($sentiment['label']) || !empty($topEmotions)) echo ' mt-2';
            ?>" role="group">
                <a
                    href="<?= htmlspecialchars($readUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    class="btn btn-outline-secondary"
                    target="_blank"
                    rel="noopener"
                    data-article-url="<?= htmlspecialchars($readUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    data-article-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                    data-article-source="<?= htmlspecialchars($feedLower, ENT_QUOTES, 'UTF-8'); ?>"
                    data-article-image="<?= htmlspecialchars((string)($row['media_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    data-article-pub-date="<?= htmlspecialchars($pubIso, ENT_QUOTES, 'UTF-8'); ?>"
                    data-article-kind="external"
                >
                    Read story
                </a>

                <?php if ($showAnalyze && $hasNlp && $analyzeUrl): ?>
                    <a href="<?= htmlspecialchars($analyzeUrl, ENT_QUOTES, 'UTF-8'); ?>"
                       class="btn btn-green btn-gray-border"
                       data-loading>
                        Analyze
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

function sn_render_article_card_archive(array $vm, array $opts = []): void {
    $title    = $vm['title'] ?? '';
    $url      = $vm['read_url'] ?? '#';
    $pubTime  = $vm['pub_human'] ?? '';     // in archive we made this g:i A
    $pubIso   = $vm['pub_iso'] ?? '';
    $ts       = $vm['pub_ts'] ?? null;

    $domain   = $vm['domain'] ?? '';
    $favicon  = $vm['favicon_url'] ?? null;

    $category = $vm['feed_name_raw'] ?? '';
    $mediaUrl = (string)($vm['row']['media_url'] ?? '');

    $badges   = $vm['badges'] ?? [];

    $cardClasses = 'scroll-history-card';
    if (!empty($vm['card_classes'])) {
        // vm['card_classes'] already starts with a leading space in your builder
        $cardClasses = trim($vm['card_classes']);
    }

    // Badge href builder (reuse same behavior as search)
    $badgeHrefBuilder = $opts['badge_href_builder'] ?? null;

    ?>
    <article
        class="article-card sn-history-item <?= htmlspecialchars($cardClasses, ENT_QUOTES, 'UTF-8'); ?>"
        data-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
        data-domain="<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>"
        data-timestamp="<?= $ts !== null ? (int)$ts : ''; ?>"
    >
        <div class="article-image-wrap">
            <img
                src="<?= htmlspecialchars($mediaUrl ?: 'assets/img/news-placeholder.jpg', ENT_QUOTES, 'UTF-8'); ?>"
                alt=""
                loading="lazy"
                decoding="async"
                onerror="this.onerror=null;this.src='assets/img/news-placeholder.jpg';"
            />

            <?php if ($domain): ?>
                <a href="https://<?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="domain-chip-link">
                    <div class="domain-chip">
                        <?php if ($favicon): ?>
                            <img class="pub-favicon"
                                 src="<?= htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8'); ?>"
                                 alt=""
                                 onerror="this.style.display='none';" />
                        <?php endif; ?>
                        <?= htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </a>
            <?php endif; ?>
        </div>

        <div class="article-body">
            <h4 class="article-title mb-0"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h4>

            <?php if (!empty($badges)) : ?>
                <div class="scroll-article-badges">
                    <?php foreach ($badges as $badge): ?>
                        <?php
                            $slug = $badge['slug'] ?? '';
                            $tooltip = $badge['tooltip'] ?? '';
                            $label = $badge['label'] ?? '';

                            $href = '#';
                            if (is_callable($badgeHrefBuilder)) {
                                $href = (string)call_user_func($badgeHrefBuilder, $slug, $vm, $opts);
                            } else {
                                if ($slug === 'deep-dive') $href = '/search.php?mode=nlp&deep_dive=1';
                                elseif ($slug === 'high-signal-publisher') $href = '/search.php?high_signal=1';
                                else $href = '/search.php';
                            }
                        ?>
                        <a class="scroll-badge scroll-badge-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>"
                           href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"
                           title="<?= htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8'); ?>"
                           data-loading>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="article-meta">
                <?= htmlspecialchars($pubTime, ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <div class="article-actions">
                <?php if (!empty($vm['analyze_url'])): ?>
                    <a class="btn btn-outline-primary btn-analyze article-link"
                       href="<?= htmlspecialchars($vm['analyze_url'], ENT_QUOTES, 'UTF-8'); ?>"
                       data-loading>
                        Analyze
                    </a>
                <?php endif; ?>

                <a class="link-read article-link"
                   href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   data-article-url="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                   data-article-title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                   data-article-source="<?= htmlspecialchars(strtolower($category), ENT_QUOTES, 'UTF-8'); ?>"
                   data-article-image="<?= htmlspecialchars($mediaUrl, ENT_QUOTES, 'UTF-8'); ?>"
                   data-article-pub-date="<?= htmlspecialchars($pubIso, ENT_QUOTES, 'UTF-8'); ?>"
                   data-article-kind="external"
                >
                    <span>Read story</span><span class="icon">↗</span>
                </a>
            </div>
        </div>
    </article>
    <?php
}