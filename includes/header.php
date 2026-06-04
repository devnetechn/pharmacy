<?php
// includes/header.php
// Expects $pageTitle to be set by the including page.
$u = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? 'Pharmacy Stock Card') ?></title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0d6efd">
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('sw.js').catch(function(){});
      });
    }
  </script>
</head>
<body>
<header class="topbar">
  <div class="brand">SOCSARGEN COUNTY HOSPITAL &mdash; Pharmacy</div>
  <nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="drugs.php">Drugs</a>
    <a href="reports.php">Reports</a>
    <?php if ($u['role'] === 'admin'): ?>
      <a href="users.php">Users</a>
    <?php endif; ?>
    <span class="who"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</span>
    <a href="logout.php" class="logout">Logout</a>
  </nav>
</header>
<main class="container">
