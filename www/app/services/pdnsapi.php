<?php
function pdns_config(): array {
    return [
        'base_url' => rtrim(settings_get('pdns.url', ''), '/'),
        'api_key' => settings_get('pdns.api_key', ''),
        'server_id' => settings_get('pdns.server_id', 'localhost'),
    ];
}

function pdns_get_rrset(string $zoneName, string $name, string $type): ?array {
    $zone = pdns_get_zone($zoneName);
    $fqdn = normalize_record_name($name, $zoneName);
    $type = strtoupper($type);

    foreach (($zone['rrsets'] ?? []) as $rrset) {
        if (
            strcasecmp($rrset['name'] ?? '', $fqdn) === 0 &&
            strcasecmp($rrset['type'] ?? '', $type) === 0
        ) {
            return $rrset;
        }
    }

    return null;
}

function pdns_replace_rrset_values(string $zoneName, string $name, string $type, int $ttl, array $values): void {
    $records = [];
    $seen = [];

    foreach ($values as $value) {
        $value = trim((string)$value);
        if ($value === '') {
            continue;
        }
        if (isset($seen[$value])) {
            continue;
        }
        $seen[$value] = true;
        $records[] = [
            'content' => $value,
            'disabled' => false,
        ];
    }

    if (!$records) {
        pdns_delete_rrset($zoneName, normalize_record_name($name, $zoneName), strtoupper($type));
        return;
    }

    pdns_replace_rrset(
        $zoneName,
        normalize_record_name($name, $zoneName),
        strtoupper($type),
        $ttl,
        $records
    );
}

function pdns_rrset_add_value(string $zoneName, string $name, string $type, string $value, int $defaultTtl = 3600): void {
    $rrset = pdns_get_rrset($zoneName, $name, $type);
    $ttl = (int)($rrset['ttl'] ?? $defaultTtl);

    $values = [];
    foreach (($rrset['records'] ?? []) as $record) {
        $values[] = $record['content'] ?? '';
    }
    $values[] = $value;

    pdns_replace_rrset_values($zoneName, $name, $type, $ttl, $values);
}

function pdns_rrset_update_value(string $zoneName, string $name, string $type, string $oldValue, string $newValue, int $defaultTtl = 3600): void {
    $rrset = pdns_get_rrset($zoneName, $name, $type);
    $ttl = (int)($rrset['ttl'] ?? $defaultTtl);

    $values = [];
    foreach (($rrset['records'] ?? []) as $record) {
        $content = $record['content'] ?? '';
        $values[] = ($content === $oldValue) ? $newValue : $content;
    }

    if (!$rrset) {
        $values = [$newValue];
    }

    pdns_replace_rrset_values($zoneName, $name, $type, $ttl, $values);
}

function pdns_rrset_delete_value(string $zoneName, string $name, string $type, string $value): void {
    $rrset = pdns_get_rrset($zoneName, $name, $type);
    if (!$rrset) {
        return;
    }

    $ttl = (int)($rrset['ttl'] ?? 3600);
    $values = [];

    foreach (($rrset['records'] ?? []) as $record) {
        $content = $record['content'] ?? '';
        if ($content !== $value) {
            $values[] = $content;
        }
    }

    pdns_replace_rrset_values($zoneName, $name, $type, $ttl, $values);
}

function pdns_request(string $method, string $path, ?array $body = null): array {
    $cfg = pdns_config();
    if (!$cfg['base_url'] || !$cfg['api_key']) throw new RuntimeException('PowerDNS settings are incomplete.');

    $ch = curl_init($cfg['base_url'] . $path);
    $headers = ['X-API-Key: ' . $cfg['api_key'], 'Accept: application/json'];
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($errno) throw new RuntimeException('PowerDNS API connection error: ' . $error);

    $data = null;
    if ($response !== '' && $response !== false) {
        $decoded = json_decode($response, true);
        $data = $decoded === null ? $response : $decoded;
    }

    if ($status >= 400) {
        $msg = is_array($data) ? json_encode($data, JSON_UNESCAPED_SLASHES) : (string)$data;
        throw new RuntimeException("PowerDNS API error ($status): " . $msg);
    }

    return ['status' => $status, 'data' => $data];
}

function pdns_server_info(): array {
    $cfg = pdns_config();
    return pdns_request('GET', '/api/v1/servers/' . rawurlencode($cfg['server_id']))['data'] ?? [];
}

function pdns_list_zones(): array {
    $cfg = pdns_config();
    return pdns_request('GET', '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones')['data'] ?? [];
}

function pdns_get_zone(string $zoneName): array {
    $cfg = pdns_config();
    return pdns_request('GET', '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones/' . rawurlencode($zoneName))['data'] ?? [];
}

function normalize_zone_name(string $zoneName): string {
    return rtrim(trim($zoneName), '.') . '.';
}

function normalize_record_name(string $name, string $zoneName): string {
    $name = trim($name);
    if ($name === '' || $name === '@') return normalize_zone_name($zoneName);
    if (substr($name, -1) === '.') return $name;
    return $name . '.' . rtrim($zoneName, '.') . '.';
}

/*
function pdns_create_zone(string $zoneName, string $primaryNs, string $hostmaster, bool $dnssec = false): void {
    $cfg = pdns_config();
    $zone = normalize_zone_name($zoneName);
    $ns = rtrim(trim($primaryNs), '.');
    $ns = substr_count($ns, '.') ? $ns . '.' : $ns . '.' . rtrim($zoneName, '.') . '.';
    $hm = rtrim(str_replace('@', '.', trim($hostmaster)), '.') . '.';

    pdns_request('POST', '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones', [
        'name' => $zone,
        'kind' => 'Native',
        'nameservers' => [$ns],
        'dnssec' => $dnssec,
        'api_rectify' => true,
    ]);

    $soa = $ns . ' ' . $hm . ' 1 10800 3600 604800 3600';
    pdns_replace_rrset($zone, $zone, 'SOA', 3600, [['content' => $soa, 'disabled' => false]]);
}
*/

function pdns_create_zone(string $zoneName, string $primaryNs, string $hostmaster, bool $dnssec = false): void {
    pdns_create_native_zone($zoneName, $primaryNs, $hostmaster, $dnssec);
}

function pdns_create_native_zone(string $zoneName, string $primaryNs, string $hostmaster, bool $dnssec = false): void {
    $cfg = pdns_config();

    $zone = normalize_zone_name($zoneName);
    $ns = rtrim(trim($primaryNs), '.');
    $ns = substr_count($ns, '.') ? $ns . '.' : $ns . '.' . rtrim($zoneName, '.') . '.';
    $hm = rtrim(str_replace('@', '.', trim($hostmaster)), '.') . '.';

    pdns_request('POST', '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones', [
        'name' => $zone,
        'kind' => 'Native',
        'nameservers' => [$ns],
        'dnssec' => $dnssec,
        'api_rectify' => true,
    ]);

    $soaContent = $ns . ' ' . $hm . ' 1 10800 3600 604800 3600';
    pdns_replace_rrset($zone, $zone, 'SOA', 3600, [
        ['content' => $soaContent, 'disabled' => false]
    ]);
}

function pdns_create_slave_zone(string $zoneName, array $masters): void {
    $cfg = pdns_config();

    $zone = normalize_zone_name($zoneName);

    $masters = array_values(array_filter(array_map(function ($value) {
        return trim($value);
    }, $masters)));

    if (!$masters) {
        throw new RuntimeException('At least one master IP is required.');
    }

    pdns_request('POST', '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones', [
        'name' => $zone,
        'kind' => 'Slave',
        'masters' => $masters,
    ]);
}

function pdns_update_slave_zone_masters(string $zoneName, array $masters): void {
    $cfg = pdns_config();

    $zone = normalize_zone_name($zoneName);
    $masters = array_values(array_filter(array_map('trim', $masters)));

    if (!$masters) {
        throw new RuntimeException('At least one master IP is required.');
    }

    $zoneData = pdns_get_zone($zone);

    pdns_request(
        'PUT',
        '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones/' . rawurlencode($zone),
        [
            'id' => $zoneData['id'] ?? $zone,
            'name' => $zoneData['name'] ?? $zone,
            'kind' => 'Slave',
            'masters' => $masters,
            'catalog' => $zoneData['catalog'] ?? '',
            'account' => $zoneData['account'] ?? '',
        ]
    );
}

function pdns_update_native_zone_meta(string $zoneName, string $primaryNs, string $hostmaster, bool $dnssec = false): void {
    $cfg = pdns_config();

    $zone = normalize_zone_name($zoneName);
    $zoneData = pdns_get_zone($zone);

    if (strcasecmp($zoneData['kind'] ?? '', 'Slave') === 0) {
        throw new RuntimeException('Native zone metadata cannot be updated for slave zones.');
    }

    $ns = rtrim(trim($primaryNs), '.');
    $ns = substr_count($ns, '.') ? $ns . '.' : $ns . '.' . rtrim($zoneName, '.') . '.';
    $hm = rtrim(str_replace('@', '.', trim($hostmaster)), '.') . '.';

    pdns_request(
        'PUT',
        '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones/' . rawurlencode($zone),
        [
            'id' => $zoneData['id'] ?? $zone,
            'name' => $zoneData['name'] ?? $zone,
            'kind' => $zoneData['kind'] ?? 'Native',
            'dnssec' => $dnssec,
            'catalog' => $zoneData['catalog'] ?? '',
            'account' => $zoneData['account'] ?? '',
	    'nameservers' => [$ns],
            'api_rectify' => true,
        ]
    );

    $soaRrset = null;
    foreach (($zoneData['rrsets'] ?? []) as $rrset) {
        if (($rrset['type'] ?? '') === 'SOA') {
            $soaRrset = $rrset;
            break;
        }
    }

    $soa = parse_soa($soaRrset['records'][0]['content'] ?? '');
    $ttl = (int)($soaRrset['ttl'] ?? 3600);

    $serial = $soa['serial'] ?? '1';
    $refresh = $soa['refresh'] ?? '10800';
    $retry = $soa['retry'] ?? '3600';
    $expire = $soa['expire'] ?? '604800';
    $minimum = $soa['minimum'] ?? '3600';

    $soaContent = $ns . ' ' . $hm . ' ' . $serial . ' ' . $refresh . ' ' . $retry . ' ' . $expire . ' ' . $minimum;

    pdns_replace_rrset($zone, $zone, 'SOA', $ttl, [
        ['content' => $soaContent, 'disabled' => false]
    ]);
}

function pdns_delete_zone(string $zoneName): void {
    $cfg = pdns_config();
    pdns_request('DELETE', '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones/' . rawurlencode($zoneName));
}

function pdns_replace_rrset(string $zoneName, string $name, string $type, int $ttl, array $records): void {
    $cfg = pdns_config();
    pdns_request('PATCH', '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones/' . rawurlencode($zoneName), [
        'rrsets' => [[
            'name' => $name,
            'type' => strtoupper($type),
            'ttl' => $ttl,
            'changetype' => 'REPLACE',
            'records' => array_values($records),
        ]]
    ]);
}

function pdns_delete_rrset(string $zoneName, string $name, string $type): void {
    $cfg = pdns_config();
    pdns_request('PATCH', '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones/' . rawurlencode($zoneName), [
        'rrsets' => [[
            'name' => $name,
            'type' => strtoupper($type),
            'changetype' => 'DELETE',
            'records' => [],
        ]]
    ]);
}

function pdns_set_dnssec(string $zoneName, bool $enabled): void {
    $cfg = pdns_config();
    pdns_request(
        'PUT',
        '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones/' . rawurlencode($zone),
        [
            'id' => $zoneData['id'] ?? $zone,
            'name' => $zoneData['name'] ?? $zone,
            'kind' => $zoneData['kind'] ?? 'Native',
            'nameservers' => [$ns],
            'dnssec' => $dnssec,
            'catalog' => $zoneData['catalog'] ?? '',
            'account' => $zoneData['account'] ?? '',
            'api_rectify' => true,
            ]
    );

    /*
    pdns_request('PUT', '/api/v1/servers/' . rawurlencode($cfg['server_id']) . '/zones/' . rawurlencode($zoneName), [
        'name' => $zoneName,
        'kind' => 'Native',
        'dnssec' => $enabled,
        'api_rectify' => true,
    ]);
    */
}

function rrsets_flatten(array $zone): array {
    $out = [];
    foreach (($zone['rrsets'] ?? []) as $rrset) {
        foreach (($rrset['records'] ?? []) as $record) {
            $out[] = [
                'name' => $rrset['name'],
                'type' => strtoupper($rrset['type'] ?? ''),
                'ttl' => $rrset['ttl'] ?? 3600,
                'content' => $record['content'] ?? '',
                'disabled' => !empty($record['disabled']),
            ];
        }
    }
    return $out;
}

function parse_soa(string $content): array {
    $parts = preg_split('/\s+/', trim($content));
    return [
        'primary_ns' => $parts[0] ?? '',
	'hostmaster' => isset($parts[1]) ? preg_replace('/\\./', '@', preg_replace('/\\.$/', '', $parts[1]), 1) : '',
        'serial' => $parts[2] ?? '',
        'refresh' => $parts[3] ?? '',
        'retry' => $parts[4] ?? '',
        'expire' => $parts[5] ?? '',
        'minimum' => $parts[6] ?? '',
    ];
}

function build_record_content(array $input): string {
    $type = strtoupper($input['type'] ?? 'A');
    switch ($type) {
        case 'A':
        case 'AAAA':
        case 'RAW':
            return trim($input['value'] ?? '');
        case 'NS':
        case 'CNAME':
        case 'PTR':
            return rtrim(trim($input['target'] ?? ''), '.') . '.';
        case 'TXT':
            return '"' . str_replace('"', '\\"', trim($input['txt'] ?? '')) . '"';
        case 'MX':
            return (int)($input['priority'] ?? 10) . ' ' . rtrim(trim($input['target'] ?? ''), '.') . '.';
        case 'SRV':
            return (int)($input['priority'] ?? 0) . ' ' .
                   (int)($input['weight'] ?? 0) . ' ' .
                   (int)($input['port'] ?? 0) . ' ' .
                   rtrim(trim($input['target'] ?? ''), '.') . '.';
        case 'TLSA':
            return trim($input['tlsa_usage'] ?? '') . ' ' .
                   trim($input['tlsa_selector'] ?? '') . ' ' .
                   trim($input['tlsa_matching_type'] ?? '') . ' ' .
                   trim($input['tlsa_cert_data'] ?? '');
        default:
            return trim($input['value'] ?? '');
    }
}
