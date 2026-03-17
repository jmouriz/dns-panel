<?php
function ensure_storage_dirs(): void {
    if (!is_dir(STORAGE_DIR)) @mkdir(STORAGE_DIR, 0775, true);
    if (!is_dir(UPLOADS_DIR)) @mkdir(UPLOADS_DIR, 0775, true);
}

function panel_is_installed(): bool {
    return file_exists(DB_FILE) && file_exists(INSTALL_LOCK);
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_check(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['_csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(400);
        echo 'Invalid CSRF token';
        exit;
    }
}

function flash_set(string $type, string $message): void {
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    if (empty($_SESSION['_flash'])) return null;
    $f = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $f;
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) redirect('index.php?action=login');
}
