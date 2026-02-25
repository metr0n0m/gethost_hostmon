<?php

declare(strict_types=1);

namespace GetHost\Modules\Resolve;

use GetHost\Core\Contracts\ApiModuleInterface;
use GetHost\Core\Http\ApiRequest;
use GetHost\Core\Http\ApiResponse;

final class ResolveModule implements ApiModuleInterface
{
    public function name(): string
    {
        return 'resolve';
    }

    public function handle(ApiRequest $request): ApiResponse
    {
        $search = $request->search();
        if ($search === '') {
            return new ApiResponse(400, "error: missing required query parameter 'search'");
        }

        require_once __DIR__ . '/../../../app/services/ResolverService.php';
        $result = resolver_resolve($search);

        $lines = [];
        $lines[] = 'query: ' . $search;
        $lines[] = 'type: ' . ($result['query_type'] ?? 'invalid');

        if (($result['query_type'] ?? '') === 'host_to_ip') {
            $lines[] = 'protocol: ' . $this->protocolLabel($result);
            $ips = $result['resolved_ips'] ?? [];
            $lines[] = 'ip: ' . (is_array($ips) && $ips ? implode(', ', $ips) : 'not found');
        } elseif (($result['query_type'] ?? '') === 'ip_to_host') {
            $host = (string)($result['resolved_host'] ?? '');
            $lines[] = 'host: ' . ($host !== '' ? $host : 'not found');
        }

        $message = trim((string)($result['message'] ?? ''));
        if ($message !== '') {
            $lines[] = 'message: ' . $message;
        }

        $status = !empty($result['success']) ? 200 : 422;
        return new ApiResponse($status, implode(PHP_EOL, $lines));
    }

    private function protocolLabel(array $result): string
    {
        $rawInput = strtolower(trim((string)($result['input'] ?? '')));
        if (str_starts_with($rawInput, 'https://')) {
            return 'https';
        }
        if (str_starts_with($rawInput, 'http://')) {
            return 'http';
        }

        $probe = $result['protocol_probe'] ?? null;
        if (!is_array($probe)) {
            return 'not specified';
        }

        $hasHttp = isset($probe['http']) && (int)$probe['http'] >= 100 && (int)$probe['http'] <= 599;
        $hasHttps = isset($probe['https']) && (int)$probe['https'] >= 100 && (int)$probe['https'] <= 599;

        if ($hasHttp && $hasHttps) {
            return 'http + https';
        }
        if ($hasHttp) {
            return 'http';
        }
        if ($hasHttps) {
            return 'https';
        }

        return 'no response on http/https';
    }
}

