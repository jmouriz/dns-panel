<?php
function auth_login(string $username, string $password): bool {
    $user = users_find_by_username($username);
    if (!$user || (int)$user['is_active'] !== 1) return false;
    if (!password_verify($password, $user['password_hash'])) return false;

    session_regenerate_id(true);
    $roles = [];
    foreach (users_get_role_ids((int)$user['id']) as $rid) {
        $role = roles_find($rid);
        if ($role) $roles[] = $role['name'];
    }

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'display_name' => $user['display_name'] ?: $user['username'],
        'theme_preference' => $user['theme_preference'] ?: 'auto',
        'roles' => $roles,
    ];
    return true;
}

function auth_logout(): void {
    $_SESSION = [];
    session_destroy();
}
