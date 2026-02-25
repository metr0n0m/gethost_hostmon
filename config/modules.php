<?php

declare(strict_types=1);

return [
    'resolve' => [
        'class' => \GetHost\Modules\Resolve\ResolveModule::class,
        'enabled' => true,
    ],
    'health' => [
        'class' => \GetHost\Modules\System\HealthModule::class,
        'enabled' => true,
    ],
    'modules' => [
        'class' => \GetHost\Modules\System\ModulesModule::class,
        'enabled' => true,
    ],
    'settings' => [
        'class' => \GetHost\Modules\System\SettingsModule::class,
        'enabled' => true,
    ],
];
