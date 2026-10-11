<?php

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