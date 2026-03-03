<?php
declare(strict_types=1);

function sn_sentiment_bucket_from_score($score): string {
    if ($score === null || $score === '' || !is_numeric($score)) return 'unknown';
    $s = (float)$score;
    if ($s >= SN_SENT_POS) return 'positive';
    if ($s <= SN_SENT_NEG) return 'negative';
    return 'neutral';
}

function sn_sentiment_emoji(string $bucket): string {
    return match (strtolower($bucket)) {
        'positive' => '🙂',
        'negative' => '☹️',
        'neutral'  => '😐',
        default    => '🤷',
    };
}

function sn_sentiment_bucket_from_nlp($nlp): string {
    if (!$nlp) return 'unknown';
    if (is_string($nlp)) {
        $nlp = json_decode($nlp, true);
        if (!is_array($nlp)) return 'unknown';
    }
    $score = $nlp['sentiment']['score'] ?? null;
    return sn_sentiment_bucket_from_score($score);
}