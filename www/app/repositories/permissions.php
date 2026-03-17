<?php
function permissions_for_user(int $userId): array {
    $sql = 'SELECT DISTINCT p.code
            FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.id
            JOIN user_roles ur ON ur.role_id = rp.role_id
            WHERE ur.user_id = ?';
    $st = db()->prepare($sql);
    $st->execute([$userId]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}
