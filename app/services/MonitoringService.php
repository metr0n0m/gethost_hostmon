<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function monitor_check_url(string $url): array
{
    $status = 'no_access';
    $httpCode = null;
    $start = microtime(true);

    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 6,
                'method' => 'HEAD',
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $headers = @get_headers($url, true, $context);
        if ($headers && isset($headers[0]) && preg_match('~\\s(\\d{3})\\s~', (string)$headers[0], $m)) {
            $httpCode = (int)$m[1];
            // Any HTTP response code means the host is reachable.
            if ($httpCode >= 100 && $httpCode <= 599) {
                $status = 'active';
            }
        }
    } catch (Throwable $e) {
        $status = 'no_access';
    }

    $responseTimeMs = (int)((microtime(true) - $start) * 1000);

    return [
        'status' => $status,
        'http_code' => $httpCode,
        'response_time_ms' => $responseTimeMs,
    ];
}

function monitor_normalize_url(string $raw): ?string
{
    $url = trim($raw);
    if ($url === '') {
        return null;
    }

    if (!preg_match('~^https?://~i', $url)) {
        $url = 'http://' . $url;
    }

    $validated = filter_var($url, FILTER_VALIDATE_URL);
    return $validated !== false ? (string)$validated : null;
}

function monitor_url_has_scheme(string $raw): bool
{
    return (bool)preg_match('~^https?://~i', trim($raw));
}

function monitor_is_reachable_check(array $check): bool
{
    $code = isset($check['http_code']) ? (int)$check['http_code'] : 0;
    return $code >= 100 && $code <= 599;
}

function monitor_try_insert_site(PDO $pdo, string $name, string $url, ?array $check = null): array
{
    $checkStmt = $pdo->prepare('SELECT id FROM sites WHERE url = :url LIMIT 1');
    $checkStmt->execute(['url' => $url]);
    $existing = $checkStmt->fetch();
    if ($existing) {
        return ['inserted' => false, 'exists' => true, 'site_id' => (int)$existing['id'], 'url' => $url];
    }

    if ($check !== null) {
        $insert = $pdo->prepare(
            'INSERT INTO sites (name, url, status, is_enabled, last_check, response_time_ms, http_code)
             VALUES (:name, :url, :status, 1, NOW(), :rt, :code)'
        );
        $insert->execute([
            'name' => $name,
            'url' => $url,
            'status' => (string)$check['status'],
            'rt' => $check['response_time_ms'],
            'code' => $check['http_code'],
        ]);
    } else {
        $insert = $pdo->prepare('INSERT INTO sites (name, url, status, is_enabled) VALUES (:name, :url, :status, 1)');
        $insert->execute(['name' => $name, 'url' => $url, 'status' => 'inactive']);
    }

    return ['inserted' => true, 'exists' => false, 'site_id' => (int)$pdo->lastInsertId(), 'url' => $url];
}

function monitor_add_site(PDO $pdo, string $name, string $urlRaw): array
{
    $name = trim($name);
    $raw = trim($urlRaw);

    if ($name === '' || $raw === '') {
        return ['ok' => false, 'error' => 'Please provide valid name and url'];
    }

    if (monitor_url_has_scheme($raw)) {
        $normalizedUrl = monitor_normalize_url($raw);
        if ($normalizedUrl === null) {
            return ['ok' => false, 'error' => 'Please provide valid name and url'];
        }

        $check = monitor_check_url($normalizedUrl);
        $saved = monitor_try_insert_site($pdo, $name, $normalizedUrl, $check);
        if ($saved['exists']) {
            return [
                'ok' => true,
                'status' => 'exists',
                'site_ids' => [(int)$saved['site_id']],
                'added_urls' => [],
                'existing_urls' => [$normalizedUrl],
                'message' => 'Site already in monitoring list',
            ];
        }

        if (monitor_is_reachable_check($check)) {
            $message = 'Added 1 endpoint to monitoring';
            $status = 'added';
        } else {
            $message = 'Added to monitoring. No response for this protocol right now';
            $status = 'added_unreachable';
        }

        return [
            'ok' => true,
            'status' => $status,
            'site_ids' => [(int)$saved['site_id']],
            'added_urls' => [$normalizedUrl],
            'existing_urls' => [],
            'message' => $message,
        ];
    }

    $httpUrl = monitor_normalize_url('http://' . $raw);
    $httpsUrl = monitor_normalize_url('https://' . $raw);
    if ($httpUrl === null || $httpsUrl === null) {
        return ['ok' => false, 'error' => 'Please provide valid name and url'];
    }

    $httpCheck = monitor_check_url($httpUrl);
    $httpsCheck = monitor_check_url($httpsUrl);

    $addedUrls = [];
    $existingUrls = [];
    $siteIds = [];

    $targets = [
        ['url' => $httpUrl, 'check' => $httpCheck],
        ['url' => $httpsUrl, 'check' => $httpsCheck],
    ];

    foreach ($targets as $item) {
        $saved = monitor_try_insert_site($pdo, $name, $item['url'], $item['check']);
        $siteIds[] = (int)$saved['site_id'];
        if ($saved['inserted']) {
            $addedUrls[] = $item['url'];
        } else {
            $existingUrls[] = $item['url'];
        }
    }

    $hasHttpReachable = monitor_is_reachable_check($httpCheck);
    $hasHttpsReachable = monitor_is_reachable_check($httpsCheck);
    $hasAnyReachable = $hasHttpReachable || $hasHttpsReachable;

    if ($addedUrls && $existingUrls && $hasAnyReachable) {
        $status = 'partial';
        $message = 'Added reachable protocol(s); some were already in monitoring list';
    } elseif ($addedUrls && !$hasAnyReachable) {
        $status = 'added_unreachable';
        $message = 'Added to monitoring. No response on HTTP/HTTPS right now';
    } elseif ($addedUrls && $hasAnyReachable) {
        $status = 'added';
        $message = 'Added reachable protocol(s) to monitoring';
    } elseif (!$addedUrls && !$hasAnyReachable) {
        $status = 'exists_unreachable';
        $message = 'Already in monitoring. No response on HTTP/HTTPS right now';
    } else {
        $status = 'exists';
        $message = 'All protocol endpoints are already in monitoring list';
    }

    return [
        'ok' => true,
        'status' => $status,
        'site_ids' => $siteIds,
        'added_urls' => $addedUrls,
        'existing_urls' => $existingUrls,
        'message' => $message,
    ];
}

function monitor_check_site_by_id(PDO $pdo, int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'error' => 'Invalid ID'];
    }

    $stmt = $pdo->prepare('SELECT url FROM sites WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['ok' => false, 'error' => 'Site not found'];
    }

    $check = monitor_check_url((string)$row['url']);

    $update = $pdo->prepare(
        'UPDATE sites
         SET status = :status,
             last_check = NOW(),
             response_time_ms = :rt,
             http_code = :code
         WHERE id = :id'
    );
    $update->execute([
        'status' => $check['status'],
        'rt' => $check['response_time_ms'],
        'code' => $check['http_code'],
        'id' => $id,
    ]);

    return ['ok' => true, 'status' => $check['status'], 'http_code' => $check['http_code']];
}

function monitor_recheck_all(PDO $pdo, bool $onlyEnabled = true): array
{
    if ($onlyEnabled) {
        $stmt = $pdo->query('SELECT id, url FROM sites WHERE is_enabled = 1 ORDER BY id DESC');
    } else {
        $stmt = $pdo->query('SELECT id, url FROM sites ORDER BY id DESC');
    }

    $rows = $stmt->fetchAll();
    $checked = 0;

    $update = $pdo->prepare(
        'UPDATE sites
         SET status = :status,
             last_check = NOW(),
             response_time_ms = :rt,
             http_code = :code
         WHERE id = :id'
    );

    foreach ($rows as $row) {
        $check = monitor_check_url((string)$row['url']);
        $update->execute([
            'status' => $check['status'],
            'rt' => $check['response_time_ms'],
            'code' => $check['http_code'],
            'id' => (int)$row['id'],
        ]);
        $checked++;
    }

    return ['ok' => true, 'checked' => $checked];
}

function monitor_set_enabled(PDO $pdo, int $id, bool $enabled): array
{
    if ($id <= 0) {
        return ['ok' => false, 'error' => 'Invalid ID'];
    }

    $stmt = $pdo->prepare('UPDATE sites SET is_enabled = :enabled WHERE id = :id');
    $stmt->execute(['enabled' => $enabled ? 1 : 0, 'id' => $id]);

    if ($stmt->rowCount() === 0) {
        return ['ok' => false, 'error' => 'Site not found'];
    }

    return ['ok' => true, 'is_enabled' => $enabled ? 1 : 0];
}

function monitor_delete_site(PDO $pdo, int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'error' => 'Invalid ID'];
    }

    $stmt = $pdo->prepare('DELETE FROM sites WHERE id = :id');
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        return ['ok' => false, 'error' => 'Site not found'];
    }

    return ['ok' => true];
}

function monitor_get_sites(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, name, url, status, is_enabled, last_check, response_time_ms, http_code
         FROM sites
         ORDER BY created_at DESC'
    );

    return $stmt->fetchAll();
}
