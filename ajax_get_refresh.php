<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/services/SettingsService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    app_json(['success' => false, 'error' => t('msg_method_not_allowed')], 405);
    exit;
}

try {
    app_json(['success' => true, 'refresh_seconds' => settings_get_refresh_seconds()]);
} catch (Throwable $e) {
    app_json(['success' => false, 'error' => t('msg_unknown_error')], 500);
}

