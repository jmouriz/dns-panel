<?php
function users_all(): array {
    return db()->query('SELECT * FROM users ORDER BY username')->fetchAll(PDO::FETCH_ASSOC);
}

function users_find(int $id): ?array {
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function users_find_by_username(string $username): ?array {
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([$username]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

function users_create(array $data): int {
    $now = time();
    $st = db()->prepare('INSERT INTO users(username,password_hash,display_name,theme_preference,is_active,created_at,updated_at)
        VALUES(?,?,?,?,?,?,?)');
    $st->execute([
        $data['username'],
        $data['password_hash'],
        $data['display_name'] ?: $data['username'],
        $data['theme_preference'] ?? 'auto',
        $data['is_active'] ?? 1,
        $now,
        $now
    ]);
    return (int)db()->lastInsertId();
}

function users_update(int $id, array $data): void {
    $sql = 'UPDATE users SET username=?, display_name=?, theme_preference=?, is_active=?, updated_at=?';
    $params = [
        $data['username'],
        $data['display_name'],
        $data['theme_preference'],
        $data['is_active'],
        time()
    ];
    if (!empty($data['password_hash'])) {
        $sql .= ', password_hash=?';
        $params[] = $data['password_hash'];
    }
    $sql .= ' WHERE id=?';
    $params[] = $id;
    db()->prepare($sql)->execute($params);
}

function users_delete(int $id): void {
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
}

function users_set_roles(int $userId, array $roleIds): void {
    db()->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$userId]);
    $st = db()->prepare('INSERT INTO user_roles(user_id, role_id) VALUES(?,?)');
    foreach ($roleIds as $rid) $st->execute([$userId, $rid]);
}

function users_get_role_ids(int $userId): array {
    $st = db()->prepare('SELECT role_id FROM user_roles WHERE user_id = ?');
    $st->execute([$userId]);
    return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
}

function users_set_zone_access(int $userId, array $zones): void {
    db()->prepare('DELETE FROM user_zone_access WHERE user_id = ?')->execute([$userId]);
    $st = db()->prepare('INSERT INTO user_zone_access(user_id, zone_name) VALUES(?,?)');
    foreach ($zones as $zone) if ($zone !== '') $st->execute([$userId, $zone]);
}

function users_get_zone_access(int $userId): array {
    $st = db()->prepare('SELECT zone_name FROM user_zone_access WHERE user_id = ? ORDER BY zone_name');
    $st->execute([$userId]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}
