<?php

declare(strict_types=1);

use GetHost\Core\Http\ApiRequest;
use GetHost\Core\Http\ApiResponse;
use GetHost\Core\Kernel\ModuleManager;
use GetHost\Core\Kernel\ModuleRegistry;
use GetHost\Core\Support\Autoload;

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../src/Core/Support/Autoload.php';
Autoload::init(dirname(__DIR__));

$moduleName = detect_module_name();
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

$manager = new ModuleManager(dirname(__DIR__));
$moduleConfig = $manager->loadConfig();
$registry = new ModuleRegistry(is_array($moduleConfig) ? $moduleConfig : []);
$module = $registry->get($moduleName);

if ($module === null) {
    $available = implode(', ', $registry->names());
    send_response(new ApiResponse(404, 'error', 'MODULE_NOT_FOUND', 'Module not found', [
        'available' => $available,
    ]));
    exit;
}

$response = $module->handle(new ApiRequest($moduleName, $search));
send_response($response);

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

function send_response(ApiResponse $response): void
{
    http_response_code($response->statusCode());
    if (request_wants_json()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response->toArray(), JSON_UNESCAPED_UNICODE);
        return;
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo $response->toPlainText();
}

function request_wants_json(): bool
{
    $format = strtolower(trim((string)($_GET['format'] ?? '')));
    if ($format === 'json') {
        return true;
    }

    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    return str_contains($accept, 'application/json');
}
