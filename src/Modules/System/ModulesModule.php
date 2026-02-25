<?php

declare(strict_types=1);

namespace GetHost\Modules\System;

use GetHost\Core\Contracts\ApiModuleInterface;
use GetHost\Core\Http\ApiRequest;
use GetHost\Core\Http\ApiResponse;
use GetHost\Core\Kernel\ModuleManager;

final class ModulesModule implements ApiModuleInterface
{
    public function name(): string
    {
        return 'modules';
    }

    public function handle(ApiRequest $request): ApiResponse
    {
        $manager = new ModuleManager(dirname(__DIR__, 3));
        $search = strtolower(trim($request->search()));

        if ($search === '' || $search === 'list') {
            return new ApiResponse(
                200,
                'ok',
                'MODULES_LIST',
                'Module states',
                ['items' => $manager->listStates()]
            );
        }

        if (str_starts_with($search, 'enable:')) {
            $name = trim(substr($search, strlen('enable:')));
            $result = $manager->setEnabled($name, true);
            if (empty($result['ok'])) {
                return new ApiResponse(422, 'error', 'MODULE_ENABLE_FAILED', (string)$result['error']);
            }
            return new ApiResponse(200, 'ok', 'MODULE_ENABLED', 'Module enabled', $result);
        }

        if (str_starts_with($search, 'disable:')) {
            $name = trim(substr($search, strlen('disable:')));
            $result = $manager->setEnabled($name, false);
            if (empty($result['ok'])) {
                return new ApiResponse(422, 'error', 'MODULE_DISABLE_FAILED', (string)$result['error']);
            }
            return new ApiResponse(200, 'ok', 'MODULE_DISABLED', 'Module disabled', $result);
        }

        return new ApiResponse(
            422,
            'error',
            'MODULES_INVALID_COMMAND',
            "Use search=list, search=enable:<module>, or search=disable:<module>"
        );
    }
}

