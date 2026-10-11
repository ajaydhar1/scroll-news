<?php

function sn_perf_enabled(): bool
{
    static $enabled = null;

    if ($enabled === null) {
        $environment = strtolower(trim((string) (getenv('APP_ENV') ?: '')));
        $requested = strtolower(trim((string) (getenv('SCROLL_NEWS_PERF_DIAGNOSTICS') ?: '')));
        $enabled = in_array($environment, ['development', 'dev', 'staging'], true)
            && in_array($requested, ['1', 'true', 'yes'], true);
    }

    return $enabled;
}

function sn_perf_pdo_options(array $options): array
{
    if (sn_perf_enabled()) {
        $options[PDO::ATTR_STATEMENT_CLASS] = [SNPerformancePDOStatement::class, []];
    }

    return $options;
}

function sn_perf_measure_connection(callable $connect): PDO
{
    if (!sn_perf_enabled()) {
        return $connect();
    }

    $started = hrtime(true);

    try {
        return $connect();
    } finally {
        $GLOBALS['sn_perf_state']['db_connection_ms'] += (hrtime(true) - $started) / 1_000_000;
    }
}

function sn_perf_record_query(float $elapsedMs): void
{
    $GLOBALS['sn_perf_state']['prepared_query_count']++;
    $GLOBALS['sn_perf_state']['prepared_query_ms'] += $elapsedMs;
}

function sn_perf_phase(string $name): void
{
    if (!sn_perf_enabled() || !preg_match('/^[a-z][a-z0-9_]{0,31}$/', $name)) {
        return;
    }

    $now = hrtime(true);
    $state = &$GLOBALS['sn_perf_state'];
    $state['phases_ms'][$state['phase']] = ($state['phases_ms'][$state['phase']] ?? 0)
        + (($now - $state['phase_started']) / 1_000_000);
    $state['phase'] = $name;
    $state['phase_started'] = $now;
}

function sn_perf_shutdown(): void
{
    $state = &$GLOBALS['sn_perf_state'];
    $now = hrtime(true);
    $state['phases_ms'][$state['phase']] = ($state['phases_ms'][$state['phase']] ?? 0)
        + (($now - $state['phase_started']) / 1_000_000);

    $metrics = [
        'php_ms' => ($now - $state['started']) / 1_000_000,
        'db_connection_ms' => $state['db_connection_ms'],
        'prepared_query_count' => $state['prepared_query_count'],
        'prepared_query_ms' => $state['prepared_query_ms'],
        'phases_ms' => $state['phases_ms'],
        'peak_memory_bytes' => memory_get_peak_usage(true),
    ];

    if (!headers_sent()) {
        $serverTiming = [
            'php;dur=' . round($metrics['php_ms'], 3),
            'db_connect;dur=' . round($metrics['db_connection_ms'], 3),
            'db_execute;dur=' . round($metrics['prepared_query_ms'], 3),
            'db_prepared;desc="' . $metrics['prepared_query_count'] . '"',
        ];

        foreach ($metrics['phases_ms'] as $phase => $duration) {
            $serverTiming[] = 'phase_' . $phase . ';dur=' . round($duration, 3);
        }

        header('Server-Timing: ' . implode(', ', $serverTiming), false);
    }

    $logRequested = strtolower(trim((string) (getenv('SCROLL_NEWS_PERF_LOG') ?: '')));
    if (in_array($logRequested, ['1', 'true', 'yes'], true)) {
        error_log('scroll_news_performance ' . json_encode($metrics, JSON_UNESCAPED_SLASHES));
    }
}

class SNPerformancePDOStatement extends PDOStatement
{
    protected function __construct()
    {
    }

    public function execute(?array $params = null): bool
    {
        $started = hrtime(true);

        try {
            return $params === null ? parent::execute() : parent::execute($params);
        } finally {
            sn_perf_record_query((hrtime(true) - $started) / 1_000_000);
        }
    }
}

if (sn_perf_enabled()) {
    $started = hrtime(true);
    $GLOBALS['sn_perf_state'] = [
        'started' => $started,
        'phase' => 'bootstrap',
        'phase_started' => $started,
        'db_connection_ms' => 0.0,
        'prepared_query_count' => 0,
        'prepared_query_ms' => 0.0,
        'phases_ms' => [],
    ];
    register_shutdown_function('sn_perf_shutdown');
}