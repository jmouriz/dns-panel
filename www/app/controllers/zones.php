<?php
function zones_clone_replace_domain(string $value, string $sourceZone, string $targetZone): string {
    $src = rtrim($sourceZone, '.');
    $dst = rtrim($targetZone, '.');

    return str_ireplace($src, $dst, $value);
}

function zones_clone_skip_rrset(string $type): bool {
    return in_array(strtoupper($type), [
        'RRSIG',
        'NSEC',
        'NSEC3',
        'NSEC3PARAM',
        'DNSKEY',
        'CDNSKEY',
        'CDS',
    ], true);
}

function handle_zones_clone(): void {
    require_permission('zones.create');

    $errors = [];
    $zones = [];
    $data = [
        'source_zone' => '',
        'target_zone' => '',
    ];

    try {
        $zones = pdns_list_zones();
        $user = current_user();
        if (!user_has_role($user, 'administrator')) {
            $allowed = users_get_zone_access((int)$user['id']);
            $zones = array_values(array_filter($zones, function ($zone) use ($allowed) {
                return in_array($zone['name'], $allowed, true);
            }));
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();

        $data['source_zone'] = normalize_zone_name($_POST['source_zone'] ?? '');
        $data['target_zone'] = normalize_zone_name($_POST['target_zone'] ?? '');

        if ($data['source_zone'] === '') {
            $errors[] = 'Source zone is required.';
        }

        if ($data['target_zone'] === '') {
            $errors[] = 'Target zone is required.';
        }

        if ($data['source_zone'] !== '' && $data['target_zone'] !== '' && $data['source_zone'] === $data['target_zone']) {
            $errors[] = 'Source and target zones must be different.';
        }

        if (!$errors) {
            try {
                $source = pdns_get_zone($data['source_zone']);
                $kind = strtoupper($source['kind'] ?? 'NATIVE');

                if ($kind === 'SLAVE') {
                    throw new RuntimeException('Slave zones cannot be copied.');
                }

                $soaRrset = null;
                foreach (($source['rrsets'] ?? []) as $rrset) {
                    if (strtoupper($rrset['type'] ?? '') === 'SOA') {
                        $soaRrset = $rrset;
                        break;
                    }
                }

                if (!$soaRrset || empty($soaRrset['records'][0]['content'])) {
                    throw new RuntimeException('Source zone has no SOA record.');
                }

                $soa = parse_soa($soaRrset['records'][0]['content']);
                $primaryNs = zones_clone_replace_domain((string)($soa['primary_ns'] ?? ''), $data['source_zone'], $data['target_zone']);
                $hostmaster = zones_clone_replace_domain((string)($soa['hostmaster'] ?? ''), $data['source_zone'], $data['target_zone']);

                $dnssec = !empty($source['dnssec']);

                if ($kind === 'MASTER') {
                    pdns_create_master_zone($data['target_zone'], $primaryNs, $hostmaster, $dnssec);
                } else {
                    pdns_create_native_zone($data['target_zone'], $primaryNs, $hostmaster, $dnssec);
                }

                foreach (($source['rrsets'] ?? []) as $rrset) {
                    $type = strtoupper($rrset['type'] ?? '');

                    if (zones_clone_skip_rrset($type)) {
                        continue;
                    }

                    $newName = zones_clone_replace_domain((string)($rrset['name'] ?? ''), $data['source_zone'], $data['target_zone']);
                    $newTtl = (int)($rrset['ttl'] ?? 3600);

                    $newRecords = [];
                    foreach (($rrset['records'] ?? []) as $record) {
                        $newRecords[] = [
                            'content' => zones_clone_replace_domain((string)($record['content'] ?? ''), $data['source_zone'], $data['target_zone']),
                            'disabled' => !empty($record['disabled']),
                        ];
                    }

                    if ($newRecords) {
                        pdns_replace_rrset($data['target_zone'], $newName, $type, $newTtl, $newRecords);
                    }
                }

                flash_set('success', 'Zone copied successfully.');
                redirect('index.php?action=records&zone=' . urlencode($data['target_zone']));
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    render('zones/clone', [
        'zones' => $zones,
        'data' => $data,
        'errors' => $errors,
        'flash' => flash_get(),
    ]);
}

function handle_zones(): void {
    require_login();
    $zones = [];
    $error = null;

    try {
        $zones = pdns_list_zones();
        $user = current_user();
        if (!user_has_role($user, 'administrator')) {
            $allowed = users_get_zone_access((int)$user['id']);
            $zones = array_values(array_filter($zones, function ($zone) use ($allowed) {
                return in_array($zone['name'], $allowed, true);
            }));
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    render('zones/list', ['zones' => $zones, 'error' => $error, 'flash' => flash_get()]);
}

function handle_zones_create(): void {
    require_permission('zones.create');

    $errors = [];
    $data = [
        'zone_type' => 'Native',
        'name' => '',
        'primary_ns' => 'ns1',
        'hostmaster' => '',
        'dnssec' => '0',
        'masters' => '',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $data = array_merge($data, $_POST);

        $zoneType = trim($data['zone_type'] ?? 'Native');
        $zoneName = trim($data['name'] ?? '');

        if ($zoneType !== 'Native' && $zoneType !== 'Master' &&
            $zoneType !== 'Slave') {
            $errors[] = 'Invalid zone type.';
        }

        if ($zoneName === '') {
            $errors[] = 'Zone name is required.';
        }

        if ($zoneType === 'Native') {
            if (trim($data['primary_ns'] ?? '') === '') {
                $errors[] = 'Primary NS is required.';
            }
            if (trim($data['hostmaster'] ?? '') === '') {
                $errors[] = 'Hostmaster is required.';
            }
        }

        if ($zoneType === 'Slave') {
            $mastersRaw = trim($data['masters'] ?? '');
            if ($mastersRaw === '') {
                $errors[] = 'At least one master IP is required.';
            }
        }

	// TODO validate Master

        if (!$errors) {
            try {
                if ($zoneType === 'Native') {
                    pdns_create_native_zone(
                        $data['name'],
                        $data['primary_ns'],
                        $data['hostmaster'],
                        ($data['dnssec'] ?? '0') === '1'
                    );
                } elseif ($zoneType === 'Master') {
                    pdns_create_master_zone(
                        $data['name'],
                        $data['primary_ns'],
                        $data['hostmaster'],
                        ($data['dnssec'] ?? '0') === '1'
                    );
                } else {
                    $masters = array_values(array_filter(array_map('trim', explode(',', $data['masters'] ?? ''))));
                    pdns_create_slave_zone($data['name'], $masters);
                }

                flash_set('success', 'Zone created successfully.');
                redirect('index.php?action=zones');
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), '409')) {
                    $errors[] = 'Zone already exists.';
                } else {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }

    render('zones/create', ['data' => $data, 'errors' => $errors]);
}

function handle_zones_edit(): void {
    require_login();

    $zone = normalize_zone_name($_GET['zone'] ?? '');
    require_zone_access('zones.create', $zone);

    $errors = [];
    $zoneData = null;
    $data = [
        'zone_type' => 'Native',
        'name' => $zone,
        'primary_ns' => '',
        'hostmaster' => '',
        'dnssec' => '0',
        'masters' => '',
    ];

    try {
        $zoneData = pdns_get_zone($zone);
        $isSlave = strcasecmp($zoneData['kind'] ?? '', 'Slave') === 0;

        //$data['zone_type'] = $isSlave ? 'Slave' : 'Native';
        $data['zone_type'] = $zoneData['kind'] ?? 'Native';
        $data['name'] = $zoneData['name'] ?? $zone;
        $data['dnssec'] = !empty($zoneData['dnssec']) ? '1' : '0';

        if ($isSlave) {
            $data['masters'] = implode(', ', $zoneData['masters'] ?? []);
        } else {
	    $soa = null;
            foreach (($zoneData['rrsets'] ?? []) as $rrset) {
                if (($rrset['type'] ?? '') === 'SOA') {
                    $soa = parse_soa($rrset['records'][0]['content'] ?? '');
                    break;
                }
            }

            $data['primary_ns'] = $zoneData['nameservers'][0] ?? ($soa['primary_ns'] ?? '');
            $data['hostmaster'] = $soa['hostmaster'] ?? '';

	    /*
            foreach (($zoneData['rrsets'] ?? []) as $rrset) {
                if (($rrset['type'] ?? '') === 'SOA') {
                    $soa = parse_soa($rrset['records'][0]['content'] ?? '');
                    $data['primary_ns'] = $soa['primary_ns'] ?? '';
                    $data['hostmaster'] = $soa['hostmaster'] ?? '';
                    break;
                }
            }
	    */
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
        csrf_check();

        $postedType = trim($_POST['zone_type'] ?? $data['zone_type']);
        if ($postedType !== $data['zone_type']) {
            $errors[] = 'Zone type cannot be changed after creation.';
        }

        if ($data['zone_type'] === 'Slave') {
            $data['masters'] = trim($_POST['masters'] ?? '');
            if ($data['masters'] === '') {
                $errors[] = 'At least one master IP is required.';
            }

            if (!$errors) {
                try {
                    $masters = array_values(array_filter(array_map('trim', explode(',', $data['masters']))));
                    pdns_update_slave_zone_masters($zone, $masters);
                    flash_set('success', 'Slave zone updated successfully.');
                    redirect('index.php?action=zones');
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        } else {
            $data['primary_ns'] = trim($_POST['primary_ns'] ?? '');
            $data['hostmaster'] = trim($_POST['hostmaster'] ?? '');
            $data['dnssec'] = ($_POST['dnssec'] ?? '0') === '1' ? '1' : '0';

            if ($data['primary_ns'] === '') {
                $errors[] = 'Primary NS is required.';
            }
            if ($data['hostmaster'] === '') {
                $errors[] = 'Hostmaster is required.';
            }

            if (!$errors) {
                try {
                    pdns_update_native_zone_meta(
                        $zone,
                        $data['primary_ns'],
                        $data['hostmaster'],
                        $data['dnssec'] === '1'
                    );
                    flash_set('success', 'Zone updated successfully.');
                    redirect('index.php?action=zones');
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }

    render('zones/edit', [
        'zone' => $zone,
        'data' => $data,
        'errors' => $errors,
        'flash' => flash_get(),
    ]);
}

function handle_zones_delete(): void {
    require_permission('zones.delete');
    $zone = normalize_zone_name($_GET['zone'] ?? '');
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        try {
            pdns_delete_zone($zone);
            flash_set('success', 'Zone deleted successfully.');
            redirect('index.php?action=zones');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    render('zones/delete', ['zone' => $zone, 'errors' => $errors]);
}

function handle_zones_soa(): void {
    require_login();
    $zone = normalize_zone_name($_GET['zone'] ?? '');
    require_zone_access('soa.edit', $zone);

    $errors = [];
    $soa = ['primary_ns' => '', 'hostmaster' => '', 'serial' => '1', 'refresh' => '10800', 'retry' => '3600', 'expire' => '604800', 'minimum' => '3600'];
    $ttl = 3600;

    try {
        $zoneData = pdns_get_zone($zone);

        if (strcasecmp($zoneData['kind'] ?? '', 'Slave') === 0) {
            $errors[] = 'SOA cannot be edited for slave zones.';
            render('zones/soa', ['zone' => $zone, 'soa' => $soa, 'ttl' => $ttl, 'errors' => $errors, 'flash' => flash_get()]);
            return;
        }

        foreach (($zoneData['rrsets'] ?? []) as $rrset) {
            if (($rrset['type'] ?? '') === 'SOA') {
                $soa = parse_soa($rrset['records'][0]['content'] ?? '');
                $ttl = (int)($rrset['ttl'] ?? 3600);
                break;
            }
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $soa = [
            'primary_ns' => trim($_POST['primary_ns'] ?? ''),
            'hostmaster' => trim($_POST['hostmaster'] ?? ''),
            'serial' => trim($_POST['serial'] ?? '1'),
            'refresh' => trim($_POST['refresh'] ?? '10800'),
            'retry' => trim($_POST['retry'] ?? '3600'),
            'expire' => trim($_POST['expire'] ?? '604800'),
            'minimum' => trim($_POST['minimum'] ?? '3600'),
        ];

        if ($soa['primary_ns'] === '') $errors[] = 'Primary NS is required.';
        if ($soa['hostmaster'] === '') $errors[] = 'Hostmaster is required.';

        if (!$errors) {
            try {
                $content = rtrim($soa['primary_ns'], '.') . '. ' .
                           rtrim(str_replace('@', '.', $soa['hostmaster']), '.') . '. ' .
                           $soa['serial'] . ' ' . $soa['refresh'] . ' ' . $soa['retry'] . ' ' . $soa['expire'] . ' ' . $soa['minimum'];
                pdns_replace_rrset($zone, $zone, 'SOA', $ttl, [['content' => $content, 'disabled' => false]]);
                flash_set('success', 'SOA updated successfully.');
                redirect('index.php?action=zones_soa&zone=' . urlencode($zone));
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    render('zones/soa', ['zone' => $zone, 'soa' => $soa, 'ttl' => $ttl, 'errors' => $errors, 'flash' => flash_get()]);
}

function handle_zones_dnssec(): void {
    require_login();
    $zone = normalize_zone_name($_GET['zone'] ?? '');
    require_zone_access('dnssec.enable', $zone);
    csrf_check();

    try {
        $zoneData = pdns_get_zone($zone);
        if (strcasecmp($zoneData['kind'] ?? '', 'Slave') === 0) {
            flash_set('danger', 'DNSSEC cannot be changed for slave zones.');
            redirect('index.php?action=zones');
        }

        pdns_set_dnssec($zone, ($_POST['enabled'] ?? '0') === '1');
        flash_set('success', 'DNSSEC updated.');
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
    }

    redirect('index.php?action=zones');
}

function zones_diag_allowed_types(): array {
    return ['SOA', 'NS', 'A', 'AAAA', 'MX', 'TXT', 'TLSA', 'CNAME', 'PTR', 'SRV'];
}

function zones_diag_default_servers(): array {
    $out = [
        'localhost' => '127.0.0.1',
    ];

    $master = trim(settings_get('dns.master_ip', ''));
    $slave = trim(settings_get('dns.slave_ip', ''));

    if ($master !== '') {
        $out['master'] = $master;
    }
    if ($slave !== '') {
        $out['slave'] = $slave;
    }

    return $out;
}

function zones_diag_fqdn(string $name, string $zone): string {
    $name = trim($name);
    if ($name === '' || $name === '@') {
        return $zone;
    }
    if (substr($name, -1) === '.') {
        return $name;
    }
    return $name . '.' . rtrim($zone, '.') . '.';
}

function zones_diag_run_dig(string $server, string $fqdn, string $type): array {
    $server = trim($server);
    $fqdn = trim($fqdn);
    $type = strtoupper(trim($type));

    if ($server === '' || $fqdn === '' || $type === '') {
        throw new RuntimeException('Server, name and type are required.');
    }

    $cmd = sprintf('dig @%s %s %s +noall +answer +authority ' .
	'+comments +norecurse 2>&1', escapeshellarg($server),
	escapeshellarg($fqdn), escapeshellarg($type)
    );

    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);

    return [
        'command' => $cmd,
        'output' => implode("\n", $output),
        'exit_code' => $exitCode,
    ];
}

function zones_diag_parse_soa_answer(string $output): ?array {
    $lines = preg_split('/\r\n|\r|\n/', trim($output));
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, ';')) {
            continue;
        }

        $parts = preg_split('/\s+/', $line, 7);
        if (count($parts) < 7) {
            continue;
        }

        if (strtoupper($parts[3]) !== 'SOA') {
            continue;
        }

        $rdata = preg_split('/\s+/', $parts[4] . ' ' . $parts[5] . ' ' . $parts[6], 7);
        if (count($rdata) < 7) {
            continue;
        }

        return [
            'mname' => $rdata[0] ?? '',
            'rname' => $rdata[1] ?? '',
            'serial' => $rdata[2] ?? '',
            'refresh' => $rdata[3] ?? '',
            'retry' => $rdata[4] ?? '',
            'expire' => $rdata[5] ?? '',
            'minimum' => $rdata[6] ?? '',
        ];
    }

    return null;
}

function handle_zones_diag(): void {
    require_login();

    $zone = normalize_zone_name($_GET['zone'] ?? '');
    require_zone_access('zones.view', $zone);

    $servers = zones_diag_default_servers();
    $types = zones_diag_allowed_types();

    $data = [
        'server_mode' => 'localhost',
        'custom_server' => '',
        'name' => '@',
        'type' => 'SOA',
    ];

    $result = null;
    $soaParsed = null;
    $compare = null;
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();

        $data['server_mode'] = $_POST['server_mode'] ?? 'localhost';
        $data['custom_server'] = trim($_POST['custom_server'] ?? '');
        $data['name'] = trim($_POST['name'] ?? '@');
        $data['type'] = strtoupper(trim($_POST['type'] ?? 'SOA'));

        if (!in_array($data['type'], $types, true)) {
            $errors[] = 'Invalid query type.';
        }

        $server = '';
        if ($data['server_mode'] === 'custom') {
            $server = $data['custom_server'];
        } else {
            $server = $servers[$data['server_mode']] ?? '';
        }

        if ($server === '') {
            $errors[] = 'Server is required.';
        }

        $fqdn = zones_diag_fqdn($data['name'], $zone);

        if (!$errors && isset($_POST['compare_soa'])) {
            $masterIp = $servers['master'] ?? '';
            $slaveIp = $servers['slave'] ?? '';

            if ($masterIp === '' || $slaveIp === '') {
                $errors[] = 'Master and slave IPs must be configured to compare SOA.';
            } else {
                try {
                    $masterResult = zones_diag_run_dig($masterIp, $zone, 'SOA');
                    $slaveResult = zones_diag_run_dig($slaveIp, $zone, 'SOA');

                    $masterSoa = zones_diag_parse_soa_answer($masterResult['output']);
                    $slaveSoa = zones_diag_parse_soa_answer($slaveResult['output']);

                    $compare = [
                        'master_ip' => $masterIp,
                        'slave_ip' => $slaveIp,
                        'master_result' => $masterResult,
                        'slave_result' => $slaveResult,
                        'master_soa' => $masterSoa,
                        'slave_soa' => $slaveSoa,
                        'in_sync' => $masterSoa && $slaveSoa && (($masterSoa['serial'] ?? '') === ($slaveSoa['serial'] ?? '')),
                    ];
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        } elseif (!$errors) {
            try {
                $result = zones_diag_run_dig($server, $fqdn, $data['type']);
                if ($data['type'] === 'SOA') {
                    $soaParsed = zones_diag_parse_soa_answer($result['output']);
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    render('zones/diag', [
        'zone' => $zone,
        'data' => $data,
        'servers' => $servers,
        'types' => $types,
        'result' => $result,
        'soaParsed' => $soaParsed,
        'compare' => $compare,
        'errors' => $errors,
        'flash' => flash_get(),
    ]);
}
