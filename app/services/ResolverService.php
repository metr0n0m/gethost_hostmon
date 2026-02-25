<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function resolver_lower(string $value): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function resolver_normalize_query(string $input): string
{
    $value = trim(resolver_lower($input));
    if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
        $host = parse_url($value, PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            return resolver_lower($host);
        }
    }
    return $value;
}

function resolver_is_valid_host(string $value): bool
{
    if ($value === '' || strlen($value) > 253) {
        return false;
    }
    if (filter_var($value, FILTER_VALIDATE_IP)) {
        return false;
    }
    if (!preg_match('/^[A-Za-z0-9.-]+$/', $value)) {
        return false;
    }
    return str_contains($value, '.');
}

function resolver_detect_input_type(string $value): string
{
    if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
        return 'ip';
    }

    if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
        $host = parse_url($value, PHP_URL_HOST);
        if (is_string($host) && resolver_is_valid_host($host)) {
            return 'host';
        }
    }

    if (resolver_is_valid_host($value)) {
        return 'host';
    }

    return 'invalid';
}

function resolver_extract_host(string $input): string
{
    if (str_starts_with($input, 'http://') || str_starts_with($input, 'https://')) {
        $host = parse_url($input, PHP_URL_HOST);
        return is_string($host) ? resolver_lower($host) : '';
    }

    return resolver_lower($input);
}

function resolver_extract_scheme(string $input): ?string
{
    if (str_starts_with($input, 'http://')) {
        return 'http';
    }
    if (str_starts_with($input, 'https://')) {
        return 'https';
    }
    return null;
}

function resolver_client_ip(array $server): string
{
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (empty($server[$header])) {
            continue;
        }

        foreach (array_map('trim', explode(',', (string)$server[$header])) as $part) {
            if (filter_var($part, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
                return $part;
            }
        }
    }

    return '0.0.0.0';
}

function resolver_detect_os(string $ua): string
{
    $map = [
        'Windows' => 'Windows',
        'Macintosh' => 'macOS',
        'Linux' => 'Linux',
        'Android' => 'Android',
        'iPhone' => 'iOS',
        'iPad' => 'iOS',
    ];
    foreach ($map as $needle => $name) {
        if (stripos($ua, $needle) !== false) {
            return $name;
        }
    }
    return 'Unknown';
}

function resolver_detect_browser(string $ua): string
{
    $map = [
        'Edg/' => 'Edge',
        'OPR/' => 'Opera',
        'Chrome/' => 'Chrome',
        'Firefox/' => 'Firefox',
        'Safari/' => 'Safari',
        'MSIE' => 'Internet Explorer',
        'Trident/' => 'Internet Explorer',
    ];
    foreach ($map as $needle => $name) {
        if (stripos($ua, $needle) !== false) {
            return $name;
        }
    }
    return 'Unknown';
}

function resolver_detect_proxy(array $server): int
{
    foreach (['HTTP_VIA', 'HTTP_X_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_PROXY_CONNECTION'] as $header) {
        if (!empty($server[$header])) {
            return 1;
        }
    }
    return 0;
}

function resolver_detect_tor(string $clientIp): int
{
    if (!filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
        return 0;
    }

    $reverse = @gethostbyaddr($clientIp);
    return (is_string($reverse) && $reverse !== '' && stripos($reverse, 'tor') !== false) ? 1 : 0;
}

function resolver_probe_protocol(string $host, string $scheme): ?int
{
    $url = $scheme . '://' . $host;
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 4,
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
            $code = (int)$m[1];
            if ($code >= 100 && $code <= 599) {
                return $code;
            }
        }
    } catch (Throwable $e) {
    }

    return null;
}

function resolver_detect_responsive_protocols(string $host): array
{
    $httpCode = resolver_probe_protocol($host, 'http');
    $httpsCode = resolver_probe_protocol($host, 'https');

    return [
        'http' => $httpCode,
        'https' => $httpsCode,
    ];
}

function resolver_log_request(PDO $pdo, array $result, array $server): void
{
    $clientIp = resolver_client_ip($server);
    $userAgent = (string)($server['HTTP_USER_AGENT'] ?? '');
    $clientProvider = @gethostbyaddr($clientIp);
    if (!is_string($clientProvider) || $clientProvider === '' || $clientProvider === $clientIp) {
        $clientProvider = null;
    }

    $counterStmt = $pdo->prepare(
        'INSERT INTO query_counters (query_type, query_value_norm, counter, last_requested_at)
         VALUES (:query_type, :query_value_norm, 1, NOW())
         ON DUPLICATE KEY UPDATE counter = counter + 1, last_requested_at = NOW()'
    );
    $counterStmt->execute([
        'query_type' => $result['query_type'],
        'query_value_norm' => $result['normalized_query'],
    ]);

    $counterSnapshotStmt = $pdo->prepare(
        'SELECT counter FROM query_counters WHERE query_type = :query_type AND query_value_norm = :query_value_norm LIMIT 1'
    );
    $counterSnapshotStmt->execute([
        'query_type' => $result['query_type'],
        'query_value_norm' => $result['normalized_query'],
    ]);
    $counterRow = $counterSnapshotStmt->fetch();
    $counterSnapshot = $counterRow ? (int)$counterRow['counter'] : 1;

    $historyStmt = $pdo->prepare(
        'INSERT INTO query_history (
            raw_query, normalized_query, query_type, result_summary, requested_at,
            client_ip, client_os, client_browser, client_provider,
            is_proxy, is_tor, user_agent, counter_snapshot
        ) VALUES (
            :raw_query, :normalized_query, :query_type, :result_summary, NOW(),
            :client_ip, :client_os, :client_browser, :client_provider,
            :is_proxy, :is_tor, :user_agent, :counter_snapshot
        )'
    );

    $historyStmt->execute([
        'raw_query' => $result['input'],
        'normalized_query' => $result['normalized_query'],
        'query_type' => $result['query_type'],
        'result_summary' => $result['result_summary'],
        'client_ip' => $clientIp,
        'client_os' => resolver_detect_os($userAgent),
        'client_browser' => resolver_detect_browser($userAgent),
        'client_provider' => $clientProvider,
        'is_proxy' => resolver_detect_proxy($server),
        'is_tor' => resolver_detect_tor($clientIp),
        'user_agent' => $userAgent,
        'counter_snapshot' => $counterSnapshot,
    ]);
}

function resolver_resolve(string $rawQuery): array
{
    $input = trim($rawQuery);
    $normalized = resolver_normalize_query($input);
    $inputType = resolver_detect_input_type($input);

    $result = [
        'success' => false,
        'query_type' => 'invalid',
        'input' => $input,
        'normalized_query' => $normalized,
        'resolved_host' => null,
        'resolved_ips' => [],
        'result_summary' => 'Invalid input',
        'message' => 'Please provide a valid IP or host',
        'can_track' => false,
        'track_host' => null,
        'track_url' => null,
        'protocol_probe' => null,
    ];

    if ($inputType === 'ip') {
        $result['query_type'] = 'ip_to_host';
        $resolved = @gethostbyaddr($normalized);

        if (is_string($resolved) && $resolved !== '' && $resolved !== $normalized) {
            $host = resolver_lower($resolved);
            $result['success'] = true;
            $result['resolved_host'] = $host;
            $result['result_summary'] = 'Host: ' . $host;
            $result['message'] = 'Host resolved';
            $result['can_track'] = true;
            $result['track_host'] = $host;
            $result['track_url'] = $host;
            return $result;
        }

        $result['result_summary'] = 'Host not found';
        $result['message'] = 'Host for this IP was not found';
        return $result;
    }

    if ($inputType === 'host') {
        $result['query_type'] = 'host_to_ip';
        $host = resolver_extract_host($input);
        $ips = [];

        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ip']) && filter_var($record['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $ips[] = $record['ip'];
                }
                if (isset($record['ipv6']) && filter_var($record['ipv6'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        $ips = array_values(array_unique($ips));
        if (!$ips) {
            $fallback = @gethostbyname($host);
            if ($fallback && $fallback !== $host && filter_var($fallback, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ips[] = $fallback;
            }
        }

        $result['success'] = true;
        $result['resolved_host'] = $host;
        $result['can_track'] = true;
        $result['track_host'] = $host;
        $scheme = resolver_extract_scheme($input);
        $result['track_url'] = $scheme ? ($scheme . '://' . $host) : $host;
        $result['protocol_probe'] = resolver_detect_responsive_protocols($host);
        $result['resolved_ips'] = $ips;

        if ($ips) {
            $result['result_summary'] = 'IP: ' . implode(', ', $ips);
            $result['message'] = 'IP resolved';
        } else {
            $result['result_summary'] = 'IP not found';
            $result['message'] = 'Unable to resolve IP for this host';
        }

        return $result;
    }

    return $result;
}
