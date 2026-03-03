<?php
declare(strict_types=1);

/**
 * Sentiment bucketing thresholds for Scroll News UI.
 * Score is expected on [-1, 1].
 *
 * NOTE: These are UI thresholds (bucketing), not model output.
 */

define('SN_SENT_POS', 0.30);

// Option A (symmetric):
// define('SN_SENT_NEG', -0.30);

// Option B (slightly looser negative, recommended for headlines/politics):
define('SN_SENT_NEG', -0.02);