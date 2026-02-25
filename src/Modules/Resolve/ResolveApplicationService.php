<?php

declare(strict_types=1);

namespace GetHost\Modules\Resolve;

use PDO;

final class ResolveApplicationService
{
    /**
     * @return array<string,mixed>
     */
    public function resolve(string $search): array
    {
        require_once __DIR__ . '/../../../app/services/ResolverService.php';
        return resolver_resolve($search);
    }

    /**
     * @param array<string,mixed> $result
     */
    public function log(PDO $pdo, array $result, array $server): void
    {
        require_once __DIR__ . '/../../../app/services/ResolverService.php';
        resolver_log_request($pdo, $result, $server);
    }
}

