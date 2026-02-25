<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/services/SettingsService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    app_json(['success' => false, 'error' => t('msg_method_not_allowed')], 405);
    exit;
}

$refreshSeconds = (int)($_POST['refresh_seconds'] ?? 0);

try {
    $result = settings_set_refresh_seconds($refreshSeconds);
    if (!$result['ok']) {
        app_json(['success' => false, 'error' => $result['error']], 422);
        exit;
    }

    app_json(['success' => true, 'refresh_seconds' => $result['refresh_seconds']]);
} catch (Throwable $e) {
    app_json(['success' => false, 'error' => t('msg_save_refresh_failed')], 500);
}
