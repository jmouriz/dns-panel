<?php
function handle_login(): void {
    if (current_user()) redirect('index.php?action=dashboard');

    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!auth_login($username, $password)) {
            $errors[] = 'Invalid username or password.';
        } else {
            redirect('index.php?action=dashboard');
        }
    }

    render('auth/login', ['errors' => $errors]);
}

function handle_logout(): void {
    auth_logout();
    redirect('index.php?action=login');
}
