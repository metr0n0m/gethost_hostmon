<?php

declare(strict_types=1);

use GetHost\Core\Http\ApiRequest;
use GetHost\Core\Http\ApiResponse;
use GetHost\Core\Kernel\ModuleRegistry;

require_once __DIR__ . '/../app/bootstrap.php';

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefix = 'GetHost\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    });
}

$moduleName = detect_module_name();
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

$moduleConfig = require __DIR__ . '/../config/modules.php';
$registry = new ModuleRegistry(is_array($moduleConfig) ? $moduleConfig : []);
$module = $registry->get($moduleName);

if ($module === null) {
    $available = implode(', ', $registry->names());
    send_text_response(new ApiResponse(404, 'error: module not found. available: ' . $available));
    exit;
}

$response = $module->handle(new ApiRequest($moduleName, $search));
send_text_response($response);

function detect_module_name(): string
{
    $uri = trim((string)($_SERVER['REQUEST_URI'] ?? ''), '/');
    $path = parse_url('/' . $uri, PHP_URL_PATH);
    if (!is_string($path)) {
        return 'resolve';
    }

    $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn ($v) => $v !== ''));
    if (!$segments) {
        return 'resolve';
    }

    if (strtolower($segments[0]) !== 'api') {
        return 'resolve';
    }

    if (!isset($segments[1]) || $segments[1] === '' || strtolower($segments[1]) === 'index.php') {
        return 'resolve';
    }

    return strtolower($segments[1]);
}

function send_text_response(ApiResponse $response): void
{
    http_response_code($response->statusCode());
    header('Content-Type: text/plain; charset=utf-8');
    echo $response->body();
}
