<?php
function handle_config(): void {
    require_permission('config.manage');

    $errors = [];
    $success = null;

    $data = [
        'panel.title' => settings_get('panel.title', 'DNS Control Panel'),
        'panel.subtitle' => settings_get('panel.subtitle', 'PowerDNS Administration'),
        'panel.logo' => settings_get('panel.logo', ''),
        'pdns.url' => settings_get('pdns.url', 'http://pdns:8081'),
        'pdns.api_key' => settings_get('pdns.api_key', ''),
        'pdns.server_id' => settings_get('pdns.server_id', 'localhost'),
        'ui.default_theme' => settings_get('ui.default_theme', 'auto'),
        'ui.allow_user_theme_override' => settings_get('ui.allow_user_theme_override', '1'),
        'dns.master_ip' => settings_get('dns.master_ip', ''),
        'dns.slave_ip' => settings_get('dns.slave_ip', ''),
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();

        foreach ($data as $k => $v) {
            if ($k !== 'panel.logo') {
                $postKey = str_replace('.', '_', $k);
                $data[$k] = trim($_POST[$postKey] ?? '');
            }
        }

        if ($data['panel.title'] === '') $errors[] = 'Panel title is required.';
        if ($data['pdns.url'] === '') $errors[] = 'PowerDNS URL is required.';
        if ($data['pdns.api_key'] === '') $errors[] = 'PowerDNS API key is required.';

        if (!empty($_FILES['panel_logo']['name']) && ($_FILES['panel_logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['panel_logo']['name'], PATHINFO_EXTENSION);
            $ext = $ext ? '.' . strtolower($ext) : '.png';
            $dest = UPLOADS_DIR . '/logo' . $ext;

            if (@move_uploaded_file($_FILES['panel_logo']['tmp_name'], $dest)) {
                $data['panel.logo'] = 'storage/uploads/' . basename($dest);
            } else {
                $errors[] = 'Could not save logo file.';
            }
        }

        if (!$errors) {
            foreach ($data as $k => $v) {
                settings_set($k, (string)$v);
            }
            $success = 'Configuration saved successfully.';
        }
    }

    render('config/edit', [
        'data' => $data,
        'errors' => $errors,
        'success' => $success,
    ]);
}
