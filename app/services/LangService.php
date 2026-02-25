<?php

declare(strict_types=1);

function lang_start_session(int $lifetimeSeconds = 259200): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => $lifetimeSeconds,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params($lifetimeSeconds, '/; samesite=Lax', '', false, true);
    }

    ini_set('session.gc_maxlifetime', (string)$lifetimeSeconds);
    session_start();
}

function lang_supported_codes(): array
{
    return ['eng', 'rus', 'heb'];
}

function lang_load_map(string $code): array
{
    $path = __DIR__ . '/../../lang/' . $code . '.php';
    if (!is_file($path)) {
        return [];
    }

    $lang = [];
    require $path;

    return isset($lang[$code]) && is_array($lang[$code]) ? $lang[$code] : [];
}

function lang_init(string $defaultCode): void
{
    lang_start_session(259200);

    $supported = lang_supported_codes();

    if (isset($_GET['lang']) && in_array($_GET['lang'], $supported, true)) {
        $_SESSION['lang'] = (string)$_GET['lang'];
    }

    if (!isset($_SESSION['lang']) || !in_array((string)$_SESSION['lang'], $supported, true)) {
        $_SESSION['lang'] = $defaultCode;
    }

    $code = (string)$_SESSION['lang'];
    $map = lang_load_map($code);

    if (!$map && $code !== $defaultCode) {
        $code = $defaultCode;
        $_SESSION['lang'] = $code;
        $map = lang_load_map($code);
    }

    $GLOBALS['__LANG_CODE__'] = $code;
    $GLOBALS['__LANG_MAP__'] = $map;
}

function lang_code(): string
{
    return isset($GLOBALS['__LANG_CODE__']) ? (string)$GLOBALS['__LANG_CODE__'] : 'eng';
}

function t(string $key): string
{
    $map = isset($GLOBALS['__LANG_MAP__']) && is_array($GLOBALS['__LANG_MAP__']) ? $GLOBALS['__LANG_MAP__'] : [];
    return array_key_exists($key, $map) ? (string)$map[$key] : $key;
}

function js_i18n_payload(): array
{
    $keys = [
        'error_enter_ip_host','request_failed','msg_site_added','msg_add_failed','msg_add_request_failed',
        'msg_action_failed','msg_recheck_failed','msg_save_refresh_failed','msg_unknown_error',
        'error_invalid_data','type_ip_host','type_host_ip','label_type','label_host','label_ip',
        'not_found','action_add_tracking','load_failed','no_sites','status_monitor_off','status_active',
        'status_no_access','status_inactive','action_activate','action_deactivate','action_delete',
        'confirm_delete','no_data','yes','no','label_protocol','protocol_http','protocol_https',
        'protocol_both','protocol_no_response','protocol_not_specified','status_active_http_error'
    ];

    $out = [];
    foreach ($keys as $key) {
        $out[$key] = t($key);
    }

    return $out;
}
