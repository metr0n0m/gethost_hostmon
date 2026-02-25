<?php

declare(strict_types=1);


function app_info_defaults(): array
{
    $about = isset($GLOBALS['APP_ABOUT_TEXT']) && is_string($GLOBALS['APP_ABOUT_TEXT'])
        ? $GLOBALS['APP_ABOUT_TEXT']
        : 'GetHost helps resolve hosts and monitor availability in real time.';

    $contacts = isset($GLOBALS['APP_CONTACTS']) && is_array($GLOBALS['APP_CONTACTS'])
        ? $GLOBALS['APP_CONTACTS']
        : [];

    return [
        'about_text' => $about,
        'contact_email' => (string)($contacts['email'] ?? ''),
        'contact_telegram' => (string)($contacts['telegram'] ?? ''),
        'contact_github' => (string)($contacts['github'] ?? ''),
    ];
}

function app_info_load_overrides(PDO $pdo): array
{
    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'app_settings'")->fetchColumn();
        if (!$exists) {
            return [];
        }

        $stmt = $pdo->prepare(
            "SELECT setting_key, setting_value
             FROM app_settings
             WHERE setting_key IN ('about_text', 'contact_email', 'contact_telegram', 'contact_github')"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $key = isset($row['setting_key']) ? (string)$row['setting_key'] : '';
            $value = isset($row['setting_value']) ? (string)$row['setting_value'] : '';
            if ($key !== '') {
                $out[$key] = $value;
            }
        }

        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function app_info_get_about_text(PDO $pdo): string
{
    $defaults = app_info_defaults();
    $overrides = app_info_load_overrides($pdo);
    return isset($overrides['about_text']) && trim((string)$overrides['about_text']) !== ''
        ? (string)$overrides['about_text']
        : (string)$defaults['about_text'];
}

function app_info_get_contacts(PDO $pdo): array
{
    $defaults = app_info_defaults();
    $overrides = app_info_load_overrides($pdo);

    return [
        'email' => trim((string)($overrides['contact_email'] ?? $defaults['contact_email'] ?? '')),
        'telegram' => trim((string)($overrides['contact_telegram'] ?? $defaults['contact_telegram'] ?? '')),
        'github' => trim((string)($overrides['contact_github'] ?? $defaults['contact_github'] ?? '')),
    ];
}
