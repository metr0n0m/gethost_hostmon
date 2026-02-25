<?php

declare(strict_types=1);

use GetHost\Core\Support\Autoload;
use GetHost\Modules\Resolve\ResolveApplicationService;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Core/Support/Autoload.php';

Autoload::init(__DIR__);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json(['success' => false, 'error' => t('msg_method_not_allowed')], 405);
    exit;
}

$rawQuery = trim((string)($_POST['query'] ?? ''));
if ($rawQuery === '') {
    app_json(['success' => false, 'error' => t('msg_pass_query')], 400);
    exit;
}

$resolver = new ResolveApplicationService();
$result = $resolver->resolve($rawQuery);

try {
    $resolver->log($pdo, $result, $_SERVER);
} catch (Throwable $e) {
}

app_json($result, $result['success'] ? 200 : 422);
