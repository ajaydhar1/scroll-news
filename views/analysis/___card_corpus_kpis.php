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
?>

<div class="card" style="margin-top:12px;">
<h3>Corpus KPIs</h3>

<div class="kpis">
    <div class="kpi">
    <div class="label"><?= htmlspecialchars((string)$ctxLabel) ?></div>
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
    <div class="val"><?= htmlspecialchars((string)($row['corpus_min_pub_date'] ?? '')) ?></div>
    </div>

    <div class="kpi">
    <div class="label">To</div>
    <div class="val"><?= htmlspecialchars((string)($row['corpus_max_pub_date'] ?? '')) ?></div>
    </div>

    <div class="kpi">
    <div class="label">Range</div>
    <div class="val">
        <?= htmlspecialchars((string)($row['time_min'] ?? '')) ?>
        →
        <?= htmlspecialchars((string)($row['time_max'] ?? '')) ?>
    </div>
    </div>
</div>
</div>
