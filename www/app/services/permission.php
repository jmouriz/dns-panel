<?php
function user_has_role(array $user, string $role): bool {
    return in_array($role, $user['roles'] ?? [], true);
}

function user_permissions(array $user): array {
    static $cache = [];
    $id = (int)$user['id'];
    if (!isset($cache[$id])) $cache[$id] = permissions_for_user($id);
    return $cache[$id];
}

function can(array $user, string $permission): bool {
    if (user_has_role($user, 'administrator')) return true;
    return in_array($permission, user_permissions($user), true);
}

function can_access_zone(array $user, string $zoneName): bool {
    if (user_has_role($user, 'administrator')) return true;
    return in_array($zoneName, users_get_zone_access((int)$user['id']), true);
}

function require_permission(string $permission): void {
    require_login();
    $user = current_user();
    if (!$user || !can($user, $permission)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function require_zone_access(string $permission, string $zoneName): void {
    require_login();
    $user = current_user();
    if (!$user || !can($user, $permission) || !can_access_zone($user, $zoneName)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}
