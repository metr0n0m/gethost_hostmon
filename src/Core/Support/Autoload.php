<?php

declare(strict_types=1);

namespace GetHost\Core\Support;

final class Autoload
{
    public static function init(string $projectRoot): void
    {
        $vendor = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (is_file($vendor)) {
            require_once $vendor;
            return;
        }

        spl_autoload_register(static function (string $class) use ($projectRoot): void {
            $prefix = 'GetHost\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $path = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR .
                str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

            if (is_file($path)) {
                require_once $path;
            }
        });
    }
}

