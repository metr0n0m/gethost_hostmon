<?php

declare(strict_types=1);

namespace GetHost\Modules\System;

use GetHost\Core\Contracts\ApiModuleInterface;
use GetHost\Core\Http\ApiRequest;
use GetHost\Core\Http\ApiResponse;
use PDO;
use Throwable;

final class SettingsModule implements ApiModuleInterface
{
    public function name(): string
    {
        return 'settings';
    }

    public function handle(ApiRequest $request): ApiResponse
    {
        $search = trim($request->search());
        if ($search === '' || strtolower($search) === 'list') {
            return $this->listSettings();
        }

        if (str_starts_with(strtolower($search), 'get:')) {
            $key = trim(substr($search, 4));
            return $this->getSetting($key);
        }

        if (str_starts_with(strtolower($search), 'set:')) {
            $payload = trim(substr($search, 4));
            $parts = explode('=', $payload, 2);
            if (count($parts) !== 2) {
                return new ApiResponse(
                    422,
                    'error',
                    'SETTINGS_INVALID_SET_FORMAT',
                    "Use search='set:key=value'"
                );
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);
            return $this->setSetting($key, $value);
        }

        return new ApiResponse(
            422,
            'error',
            'SETTINGS_INVALID_COMMAND',
            "Use search=list | get:<key> | set:<key>=<value>"
        );
    }

    private function listSettings(): ApiResponse
    {
        $pdo = $this->safePdo();
        if (!$pdo || !$this->settingsTableExists($pdo)) {
            return new ApiResponse(
                200,
                'ok',
                'SETTINGS_LIST',
                'Settings table is not configured; using config defaults only',
                ['items' => []]
            );
        }

        try {
            $rows = $pdo->query(
                "SELECT setting_key, setting_value
                 FROM app_settings
                 WHERE setting_key IN ('about_text', 'contact_email', 'contact_telegram', 'contact_github')
                 ORDER BY setting_key"
            )->fetchAll();
            return new ApiResponse(200, 'ok', 'SETTINGS_LIST', 'Settings loaded', ['items' => $rows]);
        } catch (Throwable $e) {
            return new ApiResponse(500, 'error', 'SETTINGS_LIST_FAILED', 'Failed to load settings');
        }
    }

    private function getSetting(string $key): ApiResponse
    {
        if ($key === '') {
            return new ApiResponse(422, 'error', 'SETTINGS_KEY_REQUIRED', 'Setting key is required');
        }

        $pdo = $this->safePdo();
        if (!$pdo || !$this->settingsTableExists($pdo)) {
            return new ApiResponse(404, 'error', 'SETTINGS_TABLE_NOT_CONFIGURED', 'Settings table is not configured');
        }

        try {
            $stmt = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :key LIMIT 1');
            $stmt->execute(['key' => $key]);
            $row = $stmt->fetch();
            if (!$row) {
                return new ApiResponse(404, 'error', 'SETTINGS_KEY_NOT_FOUND', 'Setting key not found');
            }

            return new ApiResponse(200, 'ok', 'SETTINGS_GET', 'Setting loaded', [
                'key' => $key,
                'value' => (string)$row['setting_value'],
            ]);
        } catch (Throwable $e) {
            return new ApiResponse(500, 'error', 'SETTINGS_GET_FAILED', 'Failed to load setting');
        }
    }

    private function setSetting(string $key, string $value): ApiResponse
    {
        if ($key === '') {
            return new ApiResponse(422, 'error', 'SETTINGS_KEY_REQUIRED', 'Setting key is required');
        }

        if (!preg_match('/^[a-z0-9_]{2,100}$/i', $key)) {
            return new ApiResponse(422, 'error', 'SETTINGS_INVALID_KEY', 'Invalid setting key');
        }

        $pdo = $this->safePdo();
        if (!$pdo || !$this->settingsTableExists($pdo)) {
            return new ApiResponse(404, 'error', 'SETTINGS_TABLE_NOT_CONFIGURED', 'Settings table is not configured');
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO app_settings (setting_key, setting_value, updated_at)
                 VALUES (:key, :value, NOW())
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
            );
            $stmt->execute(['key' => $key, 'value' => $value]);

            return new ApiResponse(200, 'ok', 'SETTINGS_SET', 'Setting updated', [
                'key' => $key,
                'value' => $value,
            ]);
        } catch (Throwable $e) {
            return new ApiResponse(500, 'error', 'SETTINGS_SET_FAILED', 'Failed to update setting');
        }
    }

    private function safePdo(): ?PDO
    {
        try {
            if (!function_exists('app_pdo')) {
                require_once dirname(__DIR__, 3) . '/app/bootstrap.php';
            }
            return function_exists('app_pdo') ? app_pdo() : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function settingsTableExists(PDO $pdo): bool
    {
        try {
            $exists = $pdo->query("SHOW TABLES LIKE 'app_settings'")->fetchColumn();
            return (bool)$exists;
        } catch (Throwable $e) {
            return false;
        }
    }
}

