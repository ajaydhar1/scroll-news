<?php
// partials/analysis/_card_corpus_kpis.php
//
// Expected vars:
// - $kpi (array; from module query; $kpi[0] used)
// - $ctxLabel (string)
// - $ctxValue (string)
// - $time_window (string)

$windowLabelMap = [
    '24h'    => 'Last 24h',
    '7d'     => 'Last 7 days',
    '30d'    => 'Last 30 days',
    'custom' => 'Custom range',
];
$windowLabel = $windowLabelMap[$time_window] ?? $time_window;

$row = $kpi[0] ?? [];

// Scoped to this card only: same convention as sn_format_pub_date(), plus a leading weekday.
if (!function_exists('sn_format_kpi_date')) {
    function sn_format_kpi_date(?string $raw): string
    {
        if (empty($raw)) return '';

        $ts = strtotime($raw);
        if ($ts === false) return '';

        try {
            $tz  = new DateTimeZone('America/New_York');
            $dt  = (new DateTimeImmutable('@' . $ts))->setTimezone($tz);
            $now = new DateTimeImmutable('now', $tz);

            $fmt = ($dt->format('Y') === $now->format('Y'))
                ? 'D, M j • g:i A T'
                : 'D, M j, Y • g:i A T';

            return $dt->format($fmt);
        } catch (Throwable $e) {
            return '';
        }
    }
}
?>

<div class="card" style="margin-top:12px;">
<h3>Corpus KPIs</h3>

<div class="kpis">
    <div class="kpi">

    <?php
      $ctxLabelUI = ($ctxLabel === 'Topic') ? 'Narrative Frame' : $ctxLabel;
    ?>

    <div class="label"><?= htmlspecialchars((string)$ctxLabelUI) ?></div>
    <div class="val"><?= htmlspecialchars((string)$ctxValue) ?></div>
    </div>

    <div class="kpi kpi-meta">
    <div class="label">Window</div>
    <div class="val"><?= htmlspecialchars((string)$windowLabel) ?></div>
    </div>

    <div class="kpi">
    <div class="label">Articles</div>
    <div class="val"><?= (int)($row['corpus_articles'] ?? 0) ?></div>
    </div>

    <div class="kpi">
    <div class="label">From</div>
    <div class="val"><?= htmlspecialchars(sn_format_kpi_date($row['corpus_min_pub_date'] ?? null)) ?></div>
    </div>

    <div class="kpi">
    <div class="label">To</div>
    <div class="val"><?= htmlspecialchars(sn_format_kpi_date($row['corpus_max_pub_date'] ?? null)) ?></div>
    </div>

    <div class="kpi">
    <div class="label">Range</div>
    <div class="val">
        <?= htmlspecialchars(sn_format_kpi_date($row['time_min'] ?? null)) ?>
        →
        <?= htmlspecialchars(sn_format_kpi_date($row['time_max'] ?? null)) ?>
    </div>
    </div>
</div>
</div>
