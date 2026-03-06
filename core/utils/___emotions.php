<?php
/**
 * Normalize emotional_reaction values into a true 100% integer distribution.
 *
 * Returns:
 * [
 *   ['label' => 'love',  'value' => 42],
 *   ['label' => 'wow',   'value' => 27],
 *   ...
 * ]
 *
 * Options:
 * - max: max number of emotions to return (null = all)
 */
function sn_normalized_emotion_distribution(array $emotionalReaction, ?int $max = null): array
{
    $clean = [];

    foreach ($emotionalReaction as $label => $value) {
        if (!is_numeric($value)) continue;

        $label = strtolower(trim((string)$label));
        $value = max(0, (float)$value);

        if ($label === '') continue;

        $clean[$label] = $value;
    }

    if (!$clean) {
        return [];
    }

    // If values look like 0..1 scores, scale uniformly.
    // This does not change the final proportions, but keeps intent clear.
    $maxVal = max($clean);
    if ($maxVal > 0 && $maxVal <= 1.00001) {
        foreach ($clean as $k => $v) {
            $clean[$k] = $v * 100.0;
        }
    }

    $total = array_sum($clean);
    if ($total <= 0) {
        return [];
    }

    $rows = [];
    foreach ($clean as $label => $value) {
        $exact = ($value / $total) * 100.0;
        $floor = (int) floor($exact);

        $rows[] = [
            'label' => $label,
            'exact' => $exact,
            'floor' => $floor,
            'frac'  => $exact - $floor,
        ];
    }

    // Largest remainder method so final integers add to exactly 100
    usort($rows, function ($a, $b) {
        $cmp = $b['frac'] <=> $a['frac'];
        if ($cmp !== 0) return $cmp;
        return $b['exact'] <=> $a['exact'];
    });

    $sumFloor = array_sum(array_column($rows, 'floor'));
    $remainder = 100 - $sumFloor;

    foreach ($rows as $i => &$row) {
        $row['value'] = $row['floor'] + ($i < $remainder ? 1 : 0);
    }
    unset($row);

    // Sort by final normalized value descending
    usort($rows, function ($a, $b) {
        $cmp = $b['value'] <=> $a['value'];
        if ($cmp !== 0) return $cmp;
        return strcmp($a['label'], $b['label']);
    });

    if ($max !== null) {
        $rows = array_slice($rows, 0, max(0, $max));
    }

    // Strip helper fields
    return array_map(function ($row) {
        return [
            'label' => $row['label'],
            'value' => $row['value'],
        ];
    }, $rows);
}