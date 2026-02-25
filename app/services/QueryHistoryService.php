<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function query_history_get(PDO $pdo): array
{
    $historyStmt = $pdo->query(
        'SELECT
            h.id,
            h.requested_at,
            h.raw_query,
            h.normalized_query,
            h.query_type,
            h.result_summary,
            h.client_ip,
            h.client_os,
            h.client_browser,
            h.client_provider,
            h.is_proxy,
            h.is_tor,
            h.counter_snapshot,
            c.counter AS total_counter
         FROM query_history h
         LEFT JOIN query_counters c
           ON c.query_type = h.query_type
          AND c.query_value_norm = h.normalized_query
         ORDER BY h.requested_at DESC
         LIMIT 300'
    );

    $topStmt = $pdo->query(
        'SELECT query_type, query_value_norm, counter, last_requested_at
         FROM query_counters
         ORDER BY counter DESC, last_requested_at DESC
         LIMIT 100'
    );

    return [
        'history' => $historyStmt->fetchAll(),
        'top' => $topStmt->fetchAll(),
    ];
}

function query_top_queries(PDO $pdo, int $limit = 5): array
{
    $limit = max(1, min(20, $limit));
    $stmt = $pdo->query(
        'SELECT query_type, query_value_norm, counter, last_requested_at
         FROM query_counters
         ORDER BY counter DESC, last_requested_at DESC
         LIMIT ' . $limit
    );
    return $stmt->fetchAll();
}
