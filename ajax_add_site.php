<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Core/Support/Autoload.php';

use GetHost\Core\Support\Autoload;
use GetHost\Modules\Monitoring\MonitoringApplicationService;

Autoload::init(__DIR__);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json(['success' => false, 'error' => t('msg_method_not_allowed')], 405);
    exit;
}

$name = (string)($_POST['name'] ?? '');
$url = (string)($_POST['url'] ?? '');

try {
    $service = new MonitoringApplicationService();
    $result = $service->addSite($pdo, $name, $url);

    if (!$result['ok']) {
        app_json(['success' => false, 'error' => $result['error']], 422);
        exit;
    }

    app_json([
        'success' => true,
        'status' => (string)($result['status'] ?? 'added'),
        'message' => (string)($result['message'] ?? ''),
        'site_ids' => $result['site_ids'] ?? [],
        'added_urls' => $result['added_urls'] ?? [],
        'existing_urls' => $result['existing_urls'] ?? [],
    ]);
} catch (Throwable $e) {
    app_json(['success' => false, 'error' => t('msg_add_failed')], 500);
}
