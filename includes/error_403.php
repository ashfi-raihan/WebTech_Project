<?php $pageTitle = 'Access Denied'; include __DIR__ . '/header.php'; ?>
<div class="container-sm" style="padding:80px 20px;">
  <div class="empty-state">
    <div class="icon">🚫</div>
    <h1>403 - Access Denied</h1>
    <p><?= e($message ?? "You don't have permission to view this page.") ?></p>
    <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary">Go Home</a>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
