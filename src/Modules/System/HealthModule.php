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
        return new ApiResponse(
            200,
            'ok',
            'HEALTH_OK',
            'Health check passed',
            [
                'module' => 'health',
                'search' => $request->search(),
            ]
        );
    }
}
