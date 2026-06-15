<?php
require_once __DIR__ . '/app/bootstrap.php';

if (PHP_SAPI === 'cli') {
    if (panel_is_installed()) {
        echo "already-installed\n";
        exit(0);
    }

    $requirements = installer_requirements();
    if (!installer_requirements_ok($requirements)) {
        fwrite(STDERR, "Installation requirements are not satisfied.\n");
        exit(1);
    }

    $apiKey = getenv('PDNS_API_KEY') ?: '';
    $password = getenv('DNS_PANEL_ADMIN_PASSWORD') ?: '';
    $username = getenv('DNS_PANEL_ADMIN_USER') ?: 'admin';
    $displayName = getenv('DNS_PANEL_ADMIN_DISPLAY_NAME') ?: $username;
    $title = getenv('DNS_PANEL_TITLE') ?: 'DNS Control Panel';
    $subtitle = getenv('DNS_PANEL_SUBTITLE') ?: 'PowerDNS Administration';
    $logo = getenv('DNS_PANEL_LOGO') ?: '';
    $pdnsUrl = getenv('PDNS_URL') ?: 'http://170.78.75.49:8081';
    $pdnsServerId = getenv('PDNS_SERVER_ID') ?: 'localhost';

    if ($apiKey === '' || $password === '') {
        fwrite(STDERR, "Missing PDNS_API_KEY or DNS_PANEL_ADMIN_PASSWORD\n");
        exit(1);
    }

    installer_run(
        [
            'title' => $title,
            'subtitle' => $subtitle,
            'default_theme' => 'auto',
            'allow_user_theme_override' => true,
            'logo' => $logo,
        ],
        [
            'url' => $pdnsUrl,
            'api_key' => $apiKey,
            'server_id' => $pdnsServerId,
        ],
        [
            'username' => $username,
            'display_name' => $displayName,
            'password' => $password,
        ]
    );

    echo "installed\n";
    exit(0);
}

if (panel_is_installed()) {
    redirect('index.php');
}

require_once __DIR__ . '/app/controllers/install.php';

$step = $_GET['step'] ?? 'requirements';
$handler = 'handle_install_' . $step;
if (!function_exists($handler)) {
    $handler = 'handle_install_requirements';
}
$handler();
