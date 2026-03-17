<?php
function handle_install_requirements(): void {
    $req = installer_requirements();
    render('install/requirements', ['requirements' => $req, 'ok' => installer_requirements_ok($req)], 'install');
}

function handle_install_panel(): void {
    $req = installer_requirements();
    if (!installer_requirements_ok($req)) redirect('install.php?step=requirements');

    $errors = [];
    $data = $_SESSION['install']['panel'] ?? [
        'title' => 'DNS Control Panel',
        'subtitle' => 'PowerDNS Administration',
        'default_theme' => 'auto',
        'allow_user_theme_override' => '1',
        'logo' => '',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $data['title'] = trim($_POST['title'] ?? '');
        $data['subtitle'] = trim($_POST['subtitle'] ?? '');
        $data['default_theme'] = $_POST['default_theme'] ?? 'auto';
        $data['allow_user_theme_override'] = $_POST['allow_user_theme_override'] ?? '1';
        if ($data['title'] === '') $errors[] = 'Panel title is required.';

        if (!empty($_FILES['logo']['name']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $ext = $ext ? '.' . strtolower($ext) : '.png';
            $dest = UPLOADS_DIR . '/logo' . $ext;
            if (@move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                $data['logo'] = 'storage/uploads/' . basename($dest);
            } else {
                $errors[] = 'Could not save logo file.';
            }
        }

        if (!$errors) {
            $_SESSION['install']['panel'] = $data;
            redirect('install.php?step=pdns');
        }
    }

    render('install/panel', ['data' => $data, 'errors' => $errors], 'install');
}

function handle_install_pdns(): void {
    if (empty($_SESSION['install']['panel'])) redirect('install.php?step=panel');

    $errors = [];
    $message = null;
    $data = $_SESSION['install']['pdns'] ?? [
        'url' => 'http://pdns:8081',
        'api_key' => '',
        'server_id' => 'localhost',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $data['url'] = trim($_POST['url'] ?? '');
        $data['api_key'] = trim($_POST['api_key'] ?? '');
        $data['server_id'] = trim($_POST['server_id'] ?? 'localhost');

        if ($data['url'] === '') $errors[] = 'PowerDNS URL is required.';
        if ($data['api_key'] === '') $errors[] = 'PowerDNS API key is required.';
        if ($data['server_id'] === '') $errors[] = 'PowerDNS server ID is required.';

        if (!$errors) {
            $test = installer_test_pdns($data['url'], $data['api_key'], $data['server_id']);
            if (!$test['ok']) {
                $errors[] = 'Connection test failed: ' . $test['message'];
            } else {
                $message = $test['message'];
                $_SESSION['install']['pdns'] = $data;
                if (isset($_POST['continue'])) redirect('install.php?step=admin');
            }
        }
    }

    render('install/pdns', ['data' => $data, 'errors' => $errors, 'message' => $message], 'install');
}

function handle_install_admin(): void {
    if (empty($_SESSION['install']['panel'])) redirect('install.php?step=panel');
    if (empty($_SESSION['install']['pdns'])) redirect('install.php?step=pdns');

    $errors = [];
    $data = ['username' => 'admin', 'display_name' => 'Administrator'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $data['username'] = trim($_POST['username'] ?? '');
        $data['display_name'] = trim($_POST['display_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password_confirm'] ?? '';

        if ($data['username'] === '') $errors[] = 'Username is required.';
        if ($password === '') $errors[] = 'Password is required.';
        if ($password !== $password2) $errors[] = 'Passwords do not match.';

        if (!$errors) {
            try {
                installer_run($_SESSION['install']['panel'], $_SESSION['install']['pdns'], [
                    'username' => $data['username'],
                    'display_name' => $data['display_name'],
                    'password' => $password,
                ]);
                unset($_SESSION['install']);
                redirect('install.php?step=done');
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    render('install/admin', ['data' => $data, 'errors' => $errors], 'install');
}

function handle_install_done(): void {
    if (!panel_is_installed()) redirect('install.php?step=requirements');
    render('install/done', [], 'install');
}
