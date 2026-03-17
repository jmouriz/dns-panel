<?php
function settings_get(string $key, ?string $default = null): ?string {
    $st = db()->prepare('SELECT value FROM settings WHERE key = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

function settings_get_all(): array {
    $rows = db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $row) $out[$row['key']] = $row['value'];
    return $out;
}

function settings_set(string $key, string $value): void {
    $st = db()->prepare('INSERT INTO settings(key, value) VALUES(?, ?)
        ON CONFLICT(key) DO UPDATE SET value=excluded.value');
    $st->execute([$key, $value]);
}
