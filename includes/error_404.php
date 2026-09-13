<?php $pageTitle = 'Page Not Found'; include __DIR__ . '/header.php'; ?>
<div class="container-sm" style="padding:80px 20px;">
  <div class="empty-state">
    <div class="icon">🔍</div>
    <h1>404 - Page Not Found</h1>
    <p>The page you're looking for doesn't exist or may have been moved.</p>
    <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary">Go Home</a>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
