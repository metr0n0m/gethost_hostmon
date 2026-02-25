<?php

declare(strict_types=1);

namespace GetHost\Modules\System;

use GetHost\Core\Contracts\ApiModuleInterface;
use GetHost\Core\Http\ApiRequest;
use GetHost\Core\Http\ApiResponse;

final class HealthModule implements ApiModuleInterface
{
    public function name(): string
    {
        return 'health';
    }

    public function handle(ApiRequest $request): ApiResponse
    {
        $lines = [
            'status: ok',
            'module: health',
            'search: ' . $request->search(),
        ];

        return new ApiResponse(200, implode(PHP_EOL, $lines));
    }
}

