<?php
/**
 * Homepage topic chips: a compact, non-sticky discovery row driven by core/topics/topics_config.php.
 */
require_once BASE_PATH . '/core/topics/___topics.php';

$snTopicChips = sn_topics_home();
if (!$snTopicChips) {
    return;
}
?>
<nav class="sn-topic-nav" aria-label="Popular topics">
    <ul class="sn-topic-nav-list">
        <?php foreach ($snTopicChips as $snTopicChip): ?>
            <li>
                <a class="sn-topic-chip"
                   href="<?= htmlspecialchars(sn_topic_url($snTopicChip['slug']), ENT_QUOTES, 'UTF-8') ?>"
                   <?php if ($snTopicChip['accent']): ?>style="--sn-topic-chip-accent: <?= htmlspecialchars($snTopicChip['accent'], ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                   data-loading>
                    <span class="sn-topic-chip-arrow" aria-hidden="true">↗</span>
                    <span class="sn-topic-chip-label"><?= htmlspecialchars($snTopicChip['name'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
