<?php
require_once __DIR__ . '/app/bootstrap.php';

if (!panel_is_installed()) {
    redirect('install.php');
}

$action = $_GET['action'] ?? 'dashboard';

/*
$routes = [
    'login' => 'auth.php',
    'logout' => 'auth.php',
    'dashboard' => 'dashboard.php',
    'zones' => 'zones.php',
    'zones_create' => 'zones.php',
    'zones_delete' => 'zones.php',
    'zones_soa' => 'zones.php',
    'zones_dnssec' => 'zones.php',
    'records' => 'records.php',
    'records_add' => 'records.php',
    'records_edit' => 'records.php',
    'records_delete' => 'records.php',
    'users' => 'users.php',
    'users_create' => 'users.php',
    'users_edit' => 'users.php',
    'users_delete' => 'users.php',
    'users_password' => 'users.php',
    'roles' => 'roles.php',
    'config' => 'config.php',
];
*/

$routes = [
    'login' => 'auth.php',
    'logout' => 'auth.php',
    'dashboard' => 'dashboard.php',
    'zones' => 'zones.php',
    'zones_create' => 'zones.php',
    'zones_edit' => 'zones.php',
    'zones_delete' => 'zones.php',
    'zones_soa' => 'zones.php',
    'zones_dnssec' => 'zones.php',
    'zones_diag' => 'zones.php',
    'zones_clone' => 'zones.php',
    'records' => 'records.php',
    'records_add' => 'records.php',
    'records_edit' => 'records.php',
    'records_delete' => 'records.php',
    'users' => 'users.php',
    'users_create' => 'users.php',
    'users_edit' => 'users.php',
    'users_delete' => 'users.php',
    'users_password' => 'users.php',
    'roles' => 'roles.php',
    'config' => 'config.php',
];

if (!isset($routes[$action])) {
    http_response_code(404);
    echo 'Unknown action';
    exit;
}

require_once __DIR__ . '/app/controllers/' . $routes[$action];
$handler = 'handle_' . $action;
if (!function_exists($handler)) {
    http_response_code(500);
    echo 'Missing handler: ' . $handler;
    exit;
}
$handler();
