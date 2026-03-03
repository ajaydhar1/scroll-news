<?php
/**
 * ___emoji_headlines.php
 * Shared headline → emoji tagging helpers (First Look, Active Headlines, etc.)
 */

function headline_emoji_candidates(string $headline): array
{
    $h = mb_strtolower($headline);

    $candidates = [];

    // Crisis / conflict / urgency
    if (preg_match('/\b(war|attack|strike|bomb|explosion|shooting|killed|dead|death|crash|collapse|hostage|terror|riot)\b/u', $h)) $candidates[] = "💥";
    if (preg_match('/\b(fire|wildfire|blaze|burning)\b/u', $h)) $candidates[] = "🔥";
    if (preg_match('/\b(breaking|urgent|alert|emergency|evacuate|warning)\b/u', $h)) $candidates[] = "🚨";

    // Politics / government
    if (preg_match('/\b(white house|president|congress|senate|house|gop|republican|democrat|election|vote|campaign|minister|secretary)\b/u', $h)) $candidates[] = "🏛️";
    if (preg_match('/\b(election|ballot|polls|primary|runoff)\b/u', $h)) $candidates[] = "🗳️";

    // Courts / crime
    if (preg_match('/\b(court|judge|trial|lawsuit|sentenced|appeal|indicted|indictment|charges)\b/u', $h)) $candidates[] = "⚖️";
    if (preg_match('/\b(arrest|police|shooting|stabbing|suspect|charged|crime|fraud|scam)\b/u', $h)) $candidates[] = "🚔";

    // Economy / business
    if (preg_match('/\b(stocks|market|inflation|recession|rates|fed|jobs report|layoffs|earnings|shares)\b/u', $h)) $candidates[] = "📉";
    if (preg_match('/\b(billion|million|funding|deal|merger|acquisition|ipo)\b/u', $h)) $candidates[] = "💰";

    // Health / science / tech
    if (preg_match('/\b(covid|flu|outbreak|health|hospital|disease|drug|fda|diabetes|weight loss)\b/u', $h)) $candidates[] = "🧠";
    if (preg_match('/\b(ai|artificial intelligence|chip|robot|space|nasa|rocket|quantum)\b/u', $h)) $candidates[] = "🤖";

    // Weird / absurd / entertainment-ish
    if (preg_match('/\b(bizarre|weird|strange|shocking|awkward|cringe)\b/u', $h)) $candidates[] = "😬";

    // Deduplicate while preserving order
    $out = [];
    foreach ($candidates as $e) {
        if (!in_array($e, $out, true)) $out[] = $e;
    }
    return $out;
}

/**
 * Pick the 1 (or optionally 2) emojis to display, based on priority.
 */
function headline_pick_emojis(string $headline, int $max = 1): array
{
    $cands = headline_emoji_candidates($headline);
    if (!$cands) return [];

    // Priority order (your list, encoded)
    $priority = ["🚨","💥","🔥","🏛️","⚖️","🚔","📉","💰","🧠","🤖","😬","🗳️"];

    usort($cands, function($a, $b) use ($priority) {
        $ia = array_search($a, $priority, true);
        $ib = array_search($b, $priority, true);
        if ($ia === false) $ia = 999;
        if ($ib === false) $ib = 999;
        return $ia <=> $ib;
    });

    $picked = array_slice($cands, 0, max(0, $max));

    // Guardrail: don't show 2 emojis unless you *really* want it later
    // For now I recommend $max = 1 everywhere.
    return $picked;
}

/**
 * Prefix headline with emoji(s) unless it already starts with an emoji.
 */
function headline_with_emojis(string $headline, int $max = 1): string
{
    $trim = ltrim($headline);

    // If it already starts with a non-word symbol (often emoji), don't double-prefix.
    // Not perfect, but works well in practice.
    if ($trim !== '' && preg_match('/^[^\p{L}\p{N}\s]/u', $trim)) {
        return $headline;
    }

    $emojis = headline_pick_emojis($headline, $max);
    if (!$emojis) return $headline;

    return implode('', $emojis) . ' ' . $headline;
}
