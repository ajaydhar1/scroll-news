<?php

function sn_select_trail_cards(array $trails, int $limit = 6): array
{
    if (empty($trails) || $limit <= 0) {
        return [];
    }

    $latestDate = max(array_column($trails, 'trail_date'));
    $latestWindowStart = date('Y-m-d', strtotime($latestDate . ' -14 days'));

    $latestWindow = array_values(array_filter($trails, static function ($trail) use ($latestWindowStart) {
        return $trail['trail_date'] >= $latestWindowStart;
    }));

    usort($latestWindow, static function ($a, $b): int {
        $dateOrder = strcmp($b['trail_date'], $a['trail_date']);
        if ($dateOrder !== 0) {
            return $dateOrder;
        }

        $recordOrder = (int) $b['total_records'] <=> (int) $a['total_records'];
        if ($recordOrder !== 0) {
            return $recordOrder;
        }

        return (int) $a['user_id'] <=> (int) $b['user_id'];
    });

    return array_slice($latestWindow, 0, $limit);
}

function sn_trail_preview_has_more(
    string $base,
    array $previewRows,
    int $visiblePreviewCount,
    callable $fetchFullTotal
): bool {
    $previewTotal = (int) ($previewRows[0]['total_trails'] ?? 0);

    if ($base === 'community') {
        return $previewTotal > $visiblePreviewCount;
    }

    if ($previewTotal > $visiblePreviewCount) {
        return true;
    }

    return (int) $fetchFullTotal() > $visiblePreviewCount;
}

function sn_resolve_filtered_trail_page(
    string $base,
    int $requestedPage,
    int $perPage,
    callable $fetchPage,
    callable $fetchTotal
): array {
    $page = max(1, $requestedPage);
    $rows = $fetchPage($base, $page, $perPage);
    $total = !empty($rows)
        ? (int) ($rows[0]['total_trails'] ?? 0)
        : (int) $fetchTotal($base);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $resolvedPage = min($page, $totalPages);

    if ($total > 0 && ($resolvedPage !== $page || empty($rows))) {
        $rows = $fetchPage($base, $resolvedPage, $perPage);
    }

    return [
        'rows' => $rows,
        'page' => $resolvedPage,
        'total_pages' => $totalPages,
        'total' => $total,
    ];
}