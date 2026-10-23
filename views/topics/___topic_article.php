<?php
/**
 * Shared renderers for topic stories. Items come from sn_topic_build_item().
 * Read Story / Analyze follow the same conventions as the Search cards
 * (data-article-* attributes feed Scroll News reading history).
 */

function sn_topic_e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** Analyze links and entity chips are hidden until more articles carry NLP analysis. */
if (!defined('SN_TOPIC_SHOW_NLP')) {
    define('SN_TOPIC_SHOW_NLP', false);
}

/** Attributes for a "read the story" link (includes the class attribute). */
function sn_topic_read_attrs(array $it, string $extraClass = ''): string
{
    $class = trim('link-read article-link ' . $extraClass);
    return 'href="' . sn_topic_e($it['url']) . '" target="_blank" rel="noopener noreferrer" class="' . sn_topic_e($class) . '"'
        . ' data-article-url="' . sn_topic_e($it['url']) . '"'
        . ' data-article-title="' . sn_topic_e($it['title']) . '"'
        . ' data-article-source="' . sn_topic_e($it['source']) . '"'
        . ' data-article-image="' . sn_topic_e($it['image'] ?? '') . '"'
        . ' data-article-pub-date="' . sn_topic_e($it['iso']) . '"'
        . ' data-article-kind="external"';
}

function sn_topic_render_headline(array $it, string $tag, string $class): void
{
    ?>
    <<?= $tag ?> class="<?= sn_topic_e($class) ?>"><a <?= sn_topic_read_attrs($it) ?>><?= sn_topic_e($it['title']) ?></a></<?= $tag ?>>
    <?php
}

/** Publisher · time, restrained. */
function sn_topic_render_meta(array $it): void
{
    $publisher = $it['publisher'];
    if ($publisher === '' && $it['rel'] === '') {
        return;
    }
    ?>
    <p class="sn-tp-meta">
        <?php if ($publisher !== ''): ?>
            <?php if ($it['publisher_url']): ?>
                <a class="sn-tp-pub" href="<?= sn_topic_e($it['publisher_url']) ?>" data-loading><?= sn_topic_e($publisher) ?></a>
            <?php else: ?>
                <span class="sn-tp-pub"><?= sn_topic_e($publisher) ?></span>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($it['rel'] !== ''): ?>
            <time datetime="<?= sn_topic_e($it['iso']) ?>"><?= sn_topic_e($it['rel']) ?></time>
        <?php endif; ?>
    </p>
    <?php
}

function sn_topic_render_entities(array $it): void
{
    if (!SN_TOPIC_SHOW_NLP || empty($it['entities'])) {
        return;
    }
    ?>
    <ul class="sn-tp-entities" aria-label="Related people and organizations">
        <?php foreach ($it['entities'] as $entity): ?>
            <li><a href="<?= sn_topic_e(sn_search_url($entity)) ?>" data-loading><?= sn_topic_e($entity) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function sn_topic_render_actions(array $it): void
{
    ?>
    <p class="sn-tp-actions">
        <a <?= sn_topic_read_attrs($it) ?>>Read story <span aria-hidden="true">↗</span></a>
        <?php if (SN_TOPIC_SHOW_NLP && $it['analyze_url']): ?>
            <a class="sn-tp-analyze" href="<?= sn_topic_e($it['analyze_url']) ?>" data-loading>Analyze</a>
        <?php endif; ?>
    </p>
    <?php
}

/** Failed or tiny images fall back to the site placeholder once; if that fails too, the image disappears (and lets the text breathe). */
function sn_topic_render_media(array $it, string $class): void
{
    if (empty($it['image'])) {
        return;
    }
    ?>
    <a <?= sn_topic_read_attrs($it, 'sn-tp-media ' . $class) ?> tabindex="-1" aria-hidden="true">
        <img src="<?= sn_topic_e($it['image']) ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"
             onload="if(this.naturalWidth<80||this.naturalHeight<80){if(this.dataset.fb){var a=this.closest('.sn-tp-media');a.parentNode.classList.add('is-imageless');a.remove();}else{this.dataset.fb=1;this.src='/assets/img/news-placeholder.jpg';}}"
             onerror="if(this.dataset.fb){var a=this.closest('.sn-tp-media');a.parentNode.classList.add('is-imageless');a.remove();}else{this.dataset.fb=1;this.src='/assets/img/news-placeholder.jpg';}">
    </a>
    <?php
}

/** Full story: image (optional), headline, meta, description, entities, actions. */
function sn_topic_render_story(array $it, string $variant, bool $showImage = true, string $headingTag = 'h3'): void
{
    $hasImage = $showImage && !empty($it['image']);
    ?>
    <article class="sn-tp-story sn-tp-story--<?= sn_topic_e($variant) ?><?= $hasImage ? ' has-image' : ' is-textual' ?>">
        <?php if ($hasImage): ?>
            <?php sn_topic_render_media($it, 'sn-tp-media--' . $variant); ?>
        <?php endif; ?>
        <div class="sn-tp-body">
            <?php sn_topic_render_meta($it); ?>
            <?php sn_topic_render_headline($it, $headingTag, 'sn-tp-headline'); ?>
            <?php if ($it['desc'] !== ''): ?>
                <p class="sn-tp-desc"><?= sn_topic_e($it['desc']) ?></p>
            <?php endif; ?>
            <?php sn_topic_render_entities($it); ?>
            <?php sn_topic_render_actions($it); ?>
        </div>
    </article>
    <?php
}

/** Rail row: text-first with strong headline hierarchy. */
function sn_topic_render_rail_item(array $it): void
{
    ?>
    <li class="sn-tp-rail-item">
        <?php sn_topic_render_meta($it); ?>
        <?php sn_topic_render_headline($it, 'h3', 'sn-tp-rail-headline'); ?>
        <?php if (SN_TOPIC_SHOW_NLP && !empty($it['analyze_url'])): ?>
            <a class="sn-tp-analyze sn-tp-rail-analyze" href="<?= sn_topic_e($it['analyze_url']) ?>" data-loading>Analyze</a>
        <?php endif; ?>
    </li>
    <?php
}
