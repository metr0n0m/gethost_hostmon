<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/services/LangService.php';

$DEFAULT_LANG = 'eng';
$DEFAULT_RECHECK_SECONDS = 30;
$APP_ABOUT_TEXT = 'GetHost is a lightweight toolkit for resolving hosts and monitoring endpoint availability with query history analytics.';
$APP_CONTACTS = [
    'email' => 'support@gethost.local',
    'telegram' => '',
    'github' => 'https://github.com/metr0n0m/gethost_hostmon',
];

lang_init($DEFAULT_LANG);

if (!isset($_SESSION['recheck_seconds']) || !is_numeric($_SESSION['recheck_seconds'])) {
    $_SESSION['recheck_seconds'] = $DEFAULT_RECHECK_SECONDS;
}

try {
    $pdo = app_pdo();
} catch (Throwable $e) {
    die(t('msg_db_error'));
}
