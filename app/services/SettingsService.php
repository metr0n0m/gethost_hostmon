<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function settings_default_refresh_seconds(): int
{
    $default = 30;
    if (isset($GLOBALS['DEFAULT_RECHECK_SECONDS'])) {
        $candidate = (int)$GLOBALS['DEFAULT_RECHECK_SECONDS'];
        if ($candidate >= 5 && $candidate <= 3600) {
            $default = $candidate;
        }
    }
    return $default;
}

function settings_get_refresh_seconds(): int
{
    $default = settings_default_refresh_seconds();
    $seconds = isset($_SESSION['recheck_seconds']) ? (int)$_SESSION['recheck_seconds'] : $default;
    if ($seconds < 5 || $seconds > 3600) {
        return $default;
    }
    return $seconds;
}

function settings_set_refresh_seconds(int $seconds): array
{
    if ($seconds < 5 || $seconds > 3600) {
        return ['ok' => false, 'error' => 'Refresh must be between 5 and 3600 seconds'];
    }

    $_SESSION['recheck_seconds'] = $seconds;

    return ['ok' => true, 'refresh_seconds' => $seconds];
}
