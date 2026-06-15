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

function handle_pdns_status(): void {
    require_login();

    $resource = trim((string)($_GET['resource'] ?? ''), '/');
    $isStyle = $resource === 'style.css';
    $query = $_GET;
    unset($query['action'], $query['resource']);

    if ($resource !== '' && !$isStyle) {
        http_response_code(404);
        echo 'Unknown PowerDNS status resource';
        return;
    }

    $baseUrl = rtrim(settings_get('pdns.url', ''), '/');
    if ($baseUrl === '') {
        http_response_code(503);
        echo 'PowerDNS URL is not configured';
        return;
    }

    $target = $baseUrl . '/' . ($isStyle ? 'style.css' : '');
    if (!$isStyle && $query) {
        $target .= '?' . http_build_query($query);
    }

    $ch = curl_init($target);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: ($isStyle ? 'text/css' : 'text/html');
    curl_close($ch);

    if ($errno) {
        http_response_code(502);
        echo 'PowerDNS status unavailable: ' . h($error);
        return;
    }

    http_response_code($status ?: 200);
    header('Content-Type: ' . $contentType);

    if ($isStyle) {
        echo $body;
        return;
    }

    $body = str_replace('href="style.css"', 'href="index.php?action=pdns_status&amp;resource=style.css"', $body);
    $body = str_replace("href='style.css'", "href='index.php?action=pdns_status&amp;resource=style.css'", $body);
    $body = str_replace('href="/"', 'href="index.php?action=pdns_status"', $body);
    $body = preg_replace('/href="\\?([^"]*)"/', 'href="index.php?action=pdns_status&amp;$1"', $body);
    $body = preg_replace("/href='\\?([^']*)'/", "href='index.php?action=pdns_status&amp;$1'", $body);

    echo $body;
}
