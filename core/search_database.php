<?php

function sn_search_database_context(
    bool $hasFilters,
    bool $isSavedShuffleView,
    ?callable $initializer = null
): array {
    $required = $hasFilters || $isSavedShuffleView;
    $pdo = null;

    if ($required) {
        $initializer = $initializer ?? static fn(): ?PDO => _pdo_or_null();
        $pdo = $initializer();
    }

    return [
        'required' => $required,
        'pdo' => $pdo,
        'error_message' => $required && !$pdo ? 'Database connection not available.' : null,
    ];
}