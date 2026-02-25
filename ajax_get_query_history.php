<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/services/QueryHistoryService.php';

try {
    $data = query_history_get($pdo);
    app_json(['success' => true, 'history' => $data['history'], 'top' => $data['top']]);
} catch (Throwable $e) {
    app_json(['success' => false, 'error' => t('request_failed')], 500);
}
