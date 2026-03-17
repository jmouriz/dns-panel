<?php
require_once __DIR__ . '/app/bootstrap.php';

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
