<?php
function installer_requirements(): array {
    ensure_storage_dirs();
    return [
        'pdo_sqlite' => extension_loaded('pdo_sqlite'),
        'sqlite3' => extension_loaded('sqlite3'),
        'curl' => extension_loaded('curl'),
        'storage' => is_writable(STORAGE_DIR),
        'uploads' => is_writable(UPLOADS_DIR),
        'schema' => file_exists(APP_DIR . '/sql/schema.sql'),
        'seed' => file_exists(APP_DIR . '/sql/seed.sql'),
    ];
}

function installer_requirements_ok(array $req): bool {
    foreach ($req as $ok) if (!$ok) return false;
    return true;
}

function installer_test_pdns(string $url, string $apiKey, string $serverId): array {
    $ch = curl_init(rtrim($url, '/') . '/api/v1/servers/' . rawurlencode($serverId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey, 'Accept: application/json'],
    ]);
    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($errno) return ['ok' => false, 'message' => $err];
    if ($status >= 400) return ['ok' => false, 'message' => 'HTTP ' . $status . ' - ' . $resp];
    return ['ok' => true, 'message' => 'Connection OK'];
}

function installer_run(array $panel, array $pdns, array $admin): void {
    if (file_exists(DB_FILE)) @unlink(DB_FILE);
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $schema = file_get_contents(APP_DIR . '/sql/schema.sql');
    $seed = file_get_contents(APP_DIR . '/sql/seed.sql');

    $pdo->beginTransaction();
    try {
        $pdo->exec($schema);
        $pdo->exec($seed);

        $now = time();
        $st = $pdo->prepare('INSERT INTO users(username,password_hash,display_name,theme_preference,is_active,created_at,updated_at)
            VALUES(?,?,?,?,1,?,?)');
        $st->execute([
            $admin['username'],
            password_hash($admin['password'], PASSWORD_DEFAULT),
            $admin['display_name'] ?: $admin['username'],
            'auto',
            $now,
            $now
        ]);

        $userId = (int)$pdo->lastInsertId();
        $roleId = (int)$pdo->query("SELECT id FROM roles WHERE name='administrator'")->fetchColumn();
        $pdo->prepare('INSERT INTO user_roles(user_id, role_id) VALUES(?,?)')->execute([$userId, $roleId]);

        $settings = [
            'app.installed' => '1',
            'panel.title' => $panel['title'],
            'panel.subtitle' => $panel['subtitle'],
            'panel.logo' => $panel['logo'] ?? '',
            'pdns.url' => rtrim($pdns['url'], '/'),
            'pdns.api_key' => $pdns['api_key'],
            'pdns.server_id' => $pdns['server_id'],
            'ui.default_theme' => $panel['default_theme'],
            'ui.allow_user_theme_override' => $panel['allow_user_theme_override'] ? '1' : '0',
        ];
        $st = $pdo->prepare('INSERT INTO settings(key,value) VALUES(?,?)');
        foreach ($settings as $k => $v) $st->execute([$k, $v]);

        $pdo->commit();
        file_put_contents(INSTALL_LOCK, (string)time());
    } catch (Throwable $e) {
        $pdo->rollBack();
        if (file_exists(DB_FILE)) @unlink(DB_FILE);
        throw $e;
    }
}
