<?php
function roles_all(): array {
    return db()->query('SELECT * FROM roles ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
}

function roles_find(int $id): ?array {
    $st = db()->prepare('SELECT * FROM roles WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function roles_permissions_map(): array {
    $sql = 'SELECT r.id role_id, r.name role_name, p.code
            FROM roles r
            LEFT JOIN role_permissions rp ON rp.role_id = r.id
            LEFT JOIN permissions p ON p.id = rp.permission_id
            ORDER BY r.name, p.code';
    $rows = db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $row) {
        $id = (int)$row['role_id'];
        if (!isset($out[$id])) $out[$id] = ['role' => ['id' => $id, 'name' => $row['role_name']], 'permissions' => []];
        if (!empty($row['code'])) $out[$id]['permissions'][] = $row['code'];
    }
    return $out;
}
