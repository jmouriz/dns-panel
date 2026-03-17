<?php
function handle_users(): void {
    require_permission('users.manage');
    render('users/list', ['users' => users_all(), 'flash' => flash_get()]);
}

function handle_users_create(): void {
    require_permission('users.manage');
    $errors = [];
    $data = ['username' => '', 'display_name' => '', 'theme_preference' => 'auto', 'is_active' => '1', 'roles' => [], 'zones' => []];
    $roles = roles_all();
    $zones = [];
    try { $zones = pdns_list_zones(); } catch (Throwable $e) { $errors[] = $e->getMessage(); }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $data['username'] = trim($_POST['username'] ?? '');
        $data['display_name'] = trim($_POST['display_name'] ?? '');
        $data['theme_preference'] = $_POST['theme_preference'] ?? 'auto';
        $data['is_active'] = $_POST['is_active'] ?? '1';
        $data['roles'] = array_map('intval', $_POST['roles'] ?? []);
        $data['zones'] = $_POST['zones'] ?? [];
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password_confirm'] ?? '';

        if ($data['username'] === '') $errors[] = 'Username is required.';
        if (users_find_by_username($data['username'])) $errors[] = 'Username already exists.';
        if ($password === '') $errors[] = 'Password is required.';
        if ($password !== $password2) $errors[] = 'Passwords do not match.';
        if (!$data['roles']) $errors[] = 'At least one role is required.';

        if (!$errors) {
            $id = users_create([
                'username' => $data['username'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'display_name' => $data['display_name'],
                'theme_preference' => $data['theme_preference'],
                'is_active' => (int)$data['is_active'],
            ]);
            users_set_roles($id, $data['roles']);
            users_set_zone_access($id, $data['zones']);
            flash_set('success', 'User created successfully.');
            redirect('index.php?action=users');
        }
    }

    render('users/form', ['mode' => 'create', 'data' => $data, 'roles' => $roles, 'zones' => $zones, 'errors' => $errors]);
}

function handle_users_edit(): void {
    require_permission('users.manage');
    $id = (int)($_GET['id'] ?? 0);
    $user = users_find($id);
    if (!$user) redirect('index.php?action=users');

    $errors = [];
    $roles = roles_all();
    $zones = [];
    try { $zones = pdns_list_zones(); } catch (Throwable $e) { $errors[] = $e->getMessage(); }

    $data = [
        'username' => $user['username'],
        'display_name' => $user['display_name'],
        'theme_preference' => $user['theme_preference'],
        'is_active' => (string)$user['is_active'],
        'roles' => users_get_role_ids($id),
        'zones' => users_get_zone_access($id),
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $data['username'] = trim($_POST['username'] ?? '');
        $data['display_name'] = trim($_POST['display_name'] ?? '');
        $data['theme_preference'] = $_POST['theme_preference'] ?? 'auto';
        $data['is_active'] = $_POST['is_active'] ?? '1';
        $data['roles'] = array_map('intval', $_POST['roles'] ?? []);
        $data['zones'] = $_POST['zones'] ?? [];
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password_confirm'] ?? '';
        $existing = users_find_by_username($data['username']);

        if ($data['username'] === '') $errors[] = 'Username is required.';
        if ($existing && (int)$existing['id'] !== $id) $errors[] = 'Username already exists.';
        if ($password !== '' && $password !== $password2) $errors[] = 'Passwords do not match.';
        if (!$data['roles']) $errors[] = 'At least one role is required.';

        if (!$errors) {
            users_update($id, [
                'username' => $data['username'],
                'display_name' => $data['display_name'],
                'theme_preference' => $data['theme_preference'],
                'is_active' => (int)$data['is_active'],
                'password_hash' => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
            ]);
            users_set_roles($id, $data['roles']);
            users_set_zone_access($id, $data['zones']);
            flash_set('success', 'User updated successfully.');
            redirect('index.php?action=users');
        }
    }

    render('users/form', ['mode' => 'edit', 'user' => $user, 'data' => $data, 'roles' => $roles, 'zones' => $zones, 'errors' => $errors]);
}

function handle_users_delete(): void {
    require_permission('users.manage');
    $id = (int)($_GET['id'] ?? 0);
    $user = users_find($id);
    if (!$user) redirect('index.php?action=users');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        users_delete($id);
        flash_set('success', 'User deleted successfully.');
        redirect('index.php?action=users');
    }

    render('users/delete', ['user' => $user, 'errors' => []]);
}

function handle_users_password(): void {
    require_login();
    $current = current_user();
    $user = users_find((int)$current['id']);
    if (!$user) {
        auth_logout();
        redirect('index.php?action=login');
    }

    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password_confirm'] ?? '';
        if ($password === '') $errors[] = 'Password is required.';
        if ($password !== $password2) $errors[] = 'Passwords do not match.';

        if (!$errors) {
            users_update((int)$user['id'], [
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'theme_preference' => $user['theme_preference'],
                'is_active' => (int)$user['is_active'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            flash_set('success', 'Password changed successfully.');
            redirect('index.php?action=dashboard');
        }
    }

    render('users/password', ['errors' => $errors]);
}
