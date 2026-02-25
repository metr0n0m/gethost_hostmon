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
            return new ApiResponse(
                400,
                'error',
                'VALIDATION_ERROR',
                "Missing required query parameter 'search'"
            );
        }

        $service = new ResolveApplicationService();
        $result = $service->resolve($search);

        $data = [
            'query' => $search,
            'type' => (string)($result['query_type'] ?? 'invalid'),
            'protocol' => $this->protocolLabel($result),
            'host' => (string)($result['resolved_host'] ?? ''),
            'ips' => is_array($result['resolved_ips'] ?? null) ? $result['resolved_ips'] : [],
            'summary' => (string)($result['result_summary'] ?? ''),
        ];

        $ok = !empty($result['success']);
        return new ApiResponse(
            $ok ? 200 : 422,
            $ok ? 'ok' : 'error',
            $ok ? 'RESOLVE_OK' : 'RESOLVE_FAILED',
            (string)($result['message'] ?? ''),
            $data
        );
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
