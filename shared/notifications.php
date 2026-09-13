<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();
$uid = currentUser()['id'];

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30');
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

// Mark all as read once viewed.
$pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);

$pageTitle = 'Notifications';
include __DIR__ . '/../includes/header.php';
?>
<div class="container-sm" style="padding:32px 20px;">
  <h1>Notifications</h1>
  <?php if (empty($items)): ?>
    <div class="empty-state"><p>No notifications yet.</p></div>
  <?php else: ?>
    <?php foreach ($items as $n): ?>
      <div class="card" style="margin-bottom:12px;">
        <p style="margin-bottom:4px;"><?= e($n['message']) ?></p>
        <p class="help-text" style="margin:0;"><?= formatDateTime($n['created_at']) ?></p>
        <?php if ($n['link']): ?><a href="<?= BASE_URL . e($n['link']) ?>" class="btn btn-outline btn-sm" style="margin-top:8px;">View</a><?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
