<?php
// public/login.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login</title><link rel="stylesheet" href="../assets/style.css"></head>
<body class="login-body">
<form method="post" class="login-card">
  <h1>Pharmacy Login</h1>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <label>Username <input name="username" required autofocus></label>
  <label>Password <input type="password" name="password" required></label>
  <button type="submit">Log In</button>
</form>
</body></html>
