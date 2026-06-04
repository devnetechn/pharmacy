<?php
// public/users.php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/db.php';
require_admin();

if (($_GET['delete'] ?? '') !== '') {
    $delId = (int)$_GET['delete'];
    if ($delId !== (int)$_SESSION['user_id']) { // don't delete yourself
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$delId]);
    }
    header('Location: users.php'); exit;
}

$users = $pdo->query('SELECT id, username, full_name, role, created_at FROM users ORDER BY username')->fetchAll();
$pageTitle = 'Users';
require __DIR__ . '/../includes/header.php';
?>
<h1>Users</h1>
<form class="inline-form" method="post" action="user_save.php">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <label>Username <input name="username" required></label>
  <label>Full name <input name="full_name" required></label>
  <label>Password <input type="password" name="password" required></label>
  <label>Role
    <select name="role"><option value="staff">staff</option><option value="admin">admin</option></select>
  </label>
  <label><button type="submit">Add User</button></label>
</form>

<table>
  <tr><th>Username</th><th>Full name</th><th>Role</th><th>Created</th><th></th></tr>
  <?php foreach ($users as $usr): ?>
  <tr>
    <td><?= htmlspecialchars($usr['username']) ?></td>
    <td><?= htmlspecialchars($usr['full_name']) ?></td>
    <td><?= htmlspecialchars($usr['role']) ?></td>
    <td><?= htmlspecialchars($usr['created_at']) ?></td>
    <td>
      <?php if ((int)$usr['id'] !== (int)$_SESSION['user_id']): ?>
        <a class="btn secondary" href="users.php?delete=<?= (int)$usr['id'] ?>"
           onclick="return confirm('Delete this user?')">Delete</a>
      <?php else: ?>(you)<?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
