<?php
require_once '/var/www/localhost/htdocs/app/bootstrap.php';

if (panel_is_installed()) {
    echo "already-installed\n";
    exit(0);
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
