<?php
// includes/auth.php
session_start();

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        die('Forbidden: admin access required.');
    }
}

function current_user(): array {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role'] ?? '',
    ];
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf(): void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(400);
        die('Invalid CSRF token.');
    }
}
