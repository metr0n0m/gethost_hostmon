<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/services/ResolverService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json(['success' => false, 'error' => t('msg_method_not_allowed')], 405);
    exit;
}

$rawQuery = trim((string)($_POST['query'] ?? ''));
if ($rawQuery === '') {
    app_json(['success' => false, 'error' => t('msg_pass_query')], 400);
    exit;
}

$result = resolver_resolve($rawQuery);

try {
    resolver_log_request($pdo, $result, $_SERVER);
} catch (Throwable $e) {
}

app_json($result, $result['success'] ? 200 : 422);
