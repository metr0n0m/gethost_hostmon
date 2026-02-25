<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/services/MonitoringService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json(['success' => false, 'error' => t('msg_method_not_allowed')], 405);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));

try {
    if ($action === 'activate') {
        $result = monitor_set_enabled($pdo, $id, true);
    } elseif ($action === 'deactivate') {
        $result = monitor_set_enabled($pdo, $id, false);
    } elseif ($action === 'delete') {
        $result = monitor_delete_site($pdo, $id);
    } else {
        app_json(['success' => false, 'error' => t('msg_action_failed')], 422);
        exit;
    }

    if (!$result['ok']) {
        app_json(['success' => false, 'error' => $result['error']], 422);
        exit;
    }

    app_json(['success' => true]);
} catch (Throwable $e) {
    app_json(['success' => false, 'error' => t('msg_action_failed')], 500);
}
