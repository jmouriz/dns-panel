<?php
function rrset_is_multi_value_type(string $type): bool {
    return in_array(strtoupper($type), ['NS', 'MX', 'TXT', 'A', 'AAAA'], true);
}

function handle_records(): void {
    require_login();
    $zone = normalize_zone_name($_GET['zone'] ?? '');
    require_zone_access('records.view', $zone);

    $records = [];
    $error = null;
    $isSlave = false;

    try {
        $zoneData = pdns_get_zone($zone);
        $records = rrsets_flatten($zoneData);
        $isSlave = strcasecmp($zoneData['kind'] ?? '', 'Slave') === 0;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    render('records/list', [
        'zone' => $zone,
        'records' => $records,
        'error' => $error,
        'flash' => flash_get(),
        'isSlave' => $isSlave,
    ]);
}

function handle_records_add(): void {
    require_login();
    $zone = normalize_zone_name($_GET['zone'] ?? '');
    require_zone_access('records.edit', $zone);

    try {
        $zoneData = pdns_get_zone($zone);
        if (strcasecmp($zoneData['kind'] ?? '', 'Slave') === 0) {
            flash_set('danger', 'Records cannot be edited for slave zones.');
            redirect('index.php?action=records&zone=' . urlencode($zone));
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
        redirect('index.php?action=records&zone=' . urlencode($zone));
    }

    $errors = [];
    $record = [
        'name' => '@',
        'ttl' => 3600,
        'type' => 'A',
        'value' => '',
        'target' => '',
        'priority' => 10,
        'weight' => 0,
        'port' => 0,
        'txt' => '',
	'tlsa_usage' => '',
        'tlsa_selector' => '',
        'tlsa_matching_type' => '',
        'tlsa_cert_data' => '',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $record = array_merge($record, $_POST);

        try {
            $name = normalize_record_name($record['name'], $zone);
            $content = build_record_content($record);
	    $type = strtoupper($record['type']);

            if ($content === '') {
                throw new RuntimeException('Record content is required.');
            }

	    if (rrset_is_multi_value_type($type)) {
                pdns_rrset_add_value($zone, $name, $type, $content, (int)$record['ttl']);
            } else {
	        pdns_replace_rrset($zone, $name, $type,
    	    	    (int) $record['ttl'],
                    [['content' => $content, 'disabled' => false]]
                );
	    }

            flash_set('success', 'Record saved successfully.');
            redirect('index.php?action=records&zone=' . urlencode($zone));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    render('records/form', [
        'mode' => 'create',
        'zone' => $zone,
        'record' => $record,
        'errors' => $errors,
    ]);
}

/*
function handle_records_edit(): void {
    require_login();
    $zone = normalize_zone_name($_GET['zone'] ?? '');
    require_zone_access('records.edit', $zone);

    try {
        $zoneData = pdns_get_zone($zone);
        if (strcasecmp($zoneData['kind'] ?? '', 'Slave') === 0) {
            flash_set('danger', 'Records cannot be edited for slave zones.');
            redirect('index.php?action=records&zone=' . urlencode($zone));
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
        redirect('index.php?action=records&zone=' . urlencode($zone));
    }

    $name = $_GET['name'] ?? '';
    $type = strtoupper($_GET['type'] ?? '');
    $content = $_GET['content'] ?? '';

    $record = [
        'name' => $name === $zone ? '@' : preg_replace('/\\.' . preg_quote(rtrim($zone, '.'), '/') . '\\.$/', '', $name),
        'ttl' => (int)($_GET['ttl'] ?? 3600),
        'type' => $type,
        'value' => '',
        'target' => '',
        'priority' => 10,
        'weight' => 0,
        'port' => 0,
        'txt' => '',
        'original_name' => $name,
        'original_type' => $type,
        'original_content' => $content,
    ];

    if ($type === 'A' || $type === 'AAAA' || $type === 'RAW') {
        $record['value'] = $content;
    } elseif (in_array($type, ['NS', 'CNAME', 'PTR'], true)) {
        $record['target'] = $content;
    } elseif ($type === 'TXT') {
        $record['txt'] = trim($content, '"');
    } elseif ($type === 'MX') {
        [$p, $t] = array_pad(preg_split('/\\s+/', $content, 2), 2, '');
        $record['priority'] = $p;
        $record['target'] = $t;
    } elseif ($type === 'SRV') {
        $parts = preg_split('/\\s+/', $content, 4);
        $record['priority'] = $parts[0] ?? 0;
        $record['weight'] = $parts[1] ?? 0;
        $record['port'] = $parts[2] ?? 0;
        $record['target'] = $parts[3] ?? '';
    }

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $record = array_merge($record, $_POST);

        try {
            $newName = normalize_record_name($record['name'], $zone);
            $newType = strtoupper($record['type']);
            $newContent = build_record_content($record);

            if ($record['original_name'] !== $newName || $record['original_type'] !== $newType) {
                pdns_delete_rrset($zone, $record['original_name'], $record['original_type']);
            }

            pdns_replace_rrset(
                $zone,
                $newName,
                $newType,
                (int)$record['ttl'],
                [['content' => $newContent, 'disabled' => false]]
            );

            flash_set('success', 'Record updated successfully.');
            redirect('index.php?action=records&zone=' . urlencode($zone));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    render('records/form', [
        'mode' => 'edit',
        'zone' => $zone,
        'record' => $record,
        'errors' => $errors,
    ]);
}
*/

function handle_records_edit(): void {
    require_login();
    $zone = normalize_zone_name($_GET['zone'] ?? '');
    require_zone_access('records.edit', $zone);

    try {
        $zoneData = pdns_get_zone($zone);
        if (strcasecmp($zoneData['kind'] ?? '', 'Slave') === 0) {
            flash_set('danger', 'Records cannot be edited for slave zones.');
            redirect('index.php?action=records&zone=' . urlencode($zone));
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
        redirect('index.php?action=records&zone=' . urlencode($zone));
    }

    $name = $_GET['name'] ?? '';
    $type = strtoupper($_GET['type'] ?? '');
    $content = $_GET['content'] ?? '';

    $record = [
        'name' => $name === $zone ? '@' : preg_replace('/\\.' . preg_quote(rtrim($zone, '.'), '/') . '\\.$/', '', $name),
        'ttl' => (int)($_GET['ttl'] ?? 3600),
        'type' => $type,
        'value' => '',
        'target' => '',
        'priority' => 10,
        'weight' => 0,
        'port' => 0,
        'txt' => '',
        'original_name' => $name,
        'original_type' => $type,
        'original_content' => $content,
	'tlsa_usage' => '',
        'tlsa_selector' => '',
        'tlsa_matching_type' => '',
        'tlsa_cert_data' => '',
    ];

    if ($type === 'A' || $type === 'AAAA' || $type === 'RAW') {
        $record['value'] = $content;
    } elseif (in_array($type, ['NS', 'CNAME', 'PTR'], true)) {
        $record['target'] = $content;
    } elseif ($type === 'TXT') {
        $record['txt'] = trim($content, '"');
    } elseif ($type === 'MX') {
        [$p, $t] = array_pad(preg_split('/\\s+/', $content, 2), 2, '');
        $record['priority'] = $p;
        $record['target'] = $t;
    } elseif ($type === 'SRV') {
        $parts = preg_split('/\\s+/', $content, 4);
        $record['priority'] = $parts[0] ?? 0;
        $record['weight'] = $parts[1] ?? 0;
        $record['port'] = $parts[2] ?? 0;
        $record['target'] = $parts[3] ?? '';
    } elseif ($type === 'TLSA') {
        $parts = preg_split('/\\s+/', trim($content), 4);
        $record['tlsa_usage'] = $parts[0] ?? '';
        $record['tlsa_selector'] = $parts[1] ?? '';
        $record['tlsa_matching_type'] = $parts[2] ?? '';
        $record['tlsa_cert_data'] = $parts[3] ?? '';
    }

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $record = array_merge($record, $_POST);

        try {
            $newName = normalize_record_name($record['name'], $zone);
            $newType = strtoupper($record['type']);
            $newContent = build_record_content($record);

	    /*
            if ($record['original_name'] !== $newName || $record['original_type'] !== $newType) {
                pdns_delete_rrset($zone, $record['original_name'], $record['original_type']);
            }

            pdns_replace_rrset(
                $zone,
                $newName,
                $newType,
                (int)$record['ttl'],
                [['content' => $newContent, 'disabled' => false]]
            );
	    */

	    if (rrset_is_multi_value_type($newType) &&
		$record['original_name'] === $newName &&
		$record['original_type'] === $newType) {
                pdns_rrset_update_value($zone, $newName, $newType,
                    $record['original_content'],
                    $newContent, (int) $record['ttl']
                );
            } else {
		if ($record['original_name'] !== $newName ||
		    $record['original_type'] !== $newType) {
                    if (rrset_is_multi_value_type($record['original_type'])) {
                        pdns_rrset_delete_value($zone,
                            $record['original_name'],
                            $record['original_type'],
                            $record['original_content']
                        );
                    } else {
			pdns_delete_rrset($zone,
			    $record['original_name'],
			    $record['original_type']
			);
                    }
                }
            
                if (rrset_is_multi_value_type($newType)) {
		    pdns_rrset_add_value($zone, $newName, $newType,
			   	         $newContent,
					 (int) $record['ttl']
			);
                } else {
		    pdns_replace_rrset(
		        $zone, $newName, $newType,
		        (int) $record['ttl'], [[
			    'content' => $newContent,
			    'disabled' => false
                    ]]);
                }
            }

            flash_set('success', 'Record updated successfully.');
            redirect('index.php?action=records&zone='.urlencode($zone));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    render('records/form', [
        'mode' => 'edit',
        'zone' => $zone,
        'record' => $record,
        'errors' => $errors,
    ]);
}

function handle_records_delete(): void {
    require_login();
    $zone = normalize_zone_name($_GET['zone'] ?? '');
    require_zone_access('records.edit', $zone);

    try {
        $zoneData = pdns_get_zone($zone);
        if (strcasecmp($zoneData['kind'] ?? '', 'Slave') === 0) {
            flash_set('danger', 'Records cannot be edited for slave zones.');
            redirect('index.php?action=records&zone=' . urlencode($zone));
        }
    } catch (Throwable $e) {
        flash_set('danger', $e->getMessage());
        redirect('index.php?action=records&zone=' . urlencode($zone));
    }

    $name = $_GET['name'] ?? '';
    $type = strtoupper($_GET['type'] ?? '');
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        try {
            if (rrset_is_multi_value_type($type)) {
                $content = $_GET['content'] ?? '';
                pdns_rrset_delete_value($zone, $name, $type, $content);
            } else {
                pdns_delete_rrset($zone, $name, $type);
            }
            flash_set('success', 'Record deleted successfully.');
            redirect('index.php?action=records&zone=' . urlencode($zone));
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    render('records/delete', [
        'zone' => $zone,
        'name' => $name,
        'type' => $type,
        'errors' => $errors,
    ]);
}
