<?php

declare(strict_types=1);

namespace GetHost\Core\Contracts;

use GetHost\Core\Http\ApiRequest;
use GetHost\Core\Http\ApiResponse;

interface ApiModuleInterface
{
    public function name(): string;

    public function handle(ApiRequest $request): ApiResponse;
}

