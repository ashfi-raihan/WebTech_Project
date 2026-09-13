<?php
// Expects $pageTitle to be set by the including page.
$__user = currentUser();
$__unread = $__user ? getUnreadNotificationCount($pdo, $__user['id']) : 0;
$__path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'SkillShare') ?> | Skill-Sharing Management System</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar">
  <div class="navbar-inner">
    <a class="brand" href="<?= BASE_URL ?>/index.php"><span class="dot"></span> SkillShare</a>
    <div class="nav-links">
      <?php if ($__user): ?>
        <a href="<?= BASE_URL ?>/<?= e($__user['role']) ?>/dashboard.php" class="<?= str_contains($__path, 'dashboard') ? 'active' : '' ?>">Dashboard</a>
        <?php if ($__user['role'] !== 'admin'): ?>
          <a href="<?= BASE_URL ?>/listings.php">Browse Skills</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/shared/notifications.php">
          Notifications
          <?php if ($__unread > 0): ?><span class="badge-count"><?= $__unread ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/<?= e($__user['role']) ?>/profile.php">Profile</a>
        <form action="<?= BASE_URL ?>/auth/logout.php" method="POST" class="inline-form">
          <button type="submit">Logout (<?= e(explode(' ', $__user['name'])[0]) ?>)</button>
        </form>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/listings.php">Browse Skills</a>
        <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-sm" style="color:#fff;">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
