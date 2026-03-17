<?php
function handle_dashboard(): void {
    require_login();
    $server = null;
    $zones = [];
    $error = null;

    try {
        $server = pdns_server_info();
        $zones = pdns_list_zones();
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    render('dashboard/index', [
        'server' => $server,
        'zones' => $zones,
        'error' => $error,
        'flash' => flash_get(),
    ]);
}
