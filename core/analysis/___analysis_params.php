<?php
// includes/analysis_params.php

function analysis_read_params(): array {

    $allowed_contexts   = ['entity','pub','topic','sent','category'];
    $allowed_windows    = ['24h','7d','30d','custom'];
    $allowed_categories = ['politics','sports','business','technology','science','health','entertainment'];
    $allowed_sent       = ['positive','neutral','negative','unknown'];

    $context = strtolower(get_param('context', 'category'));
    if (!in_array($context, $allowed_contexts, true)) $context = 'category';

    $defaultValueByContext = [
        'category' => 'politics',
        'sent'     => 'unknown',
        'pub'      => '',
        'topic'    => '',
        'entity'   => '',
    ];

    $value = get_param('value', $defaultValueByContext[$context] ?? '');
    $value = trim($value);
    if (strlen($value) > 200) $value = substr($value, 0, 200);

    if ($context === 'category') {
        $value = strtolower($value);
        if (!in_array($value, $allowed_categories, true)) $value = 'politics';
    }

    if ($context === 'sent') {
        $value = strtolower($value);
        if (!in_array($value, $allowed_sent, true)) $value = 'unknown';
    }

    if ($context === 'pub') {
        $value = strtolower($value);
        $value = preg_replace('~^https?://~', '', $value);
        $value = preg_replace('~^www\.~', '', $value);
        $value = preg_replace('~/.*$~', '', $value);
    }

    $time_window = get_param('w', '7d');
    if (!in_array($time_window, $allowed_windows, true)) $time_window = '7d';

    $custom_from = get_param('from', null);
    $custom_to   = get_param('to', null);

    return [
        'context'      => $context,
        'value'        => $value,
        'time_window'  => $time_window,
        'custom_from'  => $custom_from,
        'custom_to'    => $custom_to,
    ];
}