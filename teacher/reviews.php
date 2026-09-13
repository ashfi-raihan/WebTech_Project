<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

$stmt = $pdo->prepare("SELECT r.*, u.name AS reviewer_name FROM reviews r JOIN users u ON u.id = r.reviewer_id WHERE r.reviewee_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$tid]);
$reviews = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT COALESCE(AVG(rating),0) a, COUNT(*) c FROM reviews WHERE reviewee_id = ?');
$stmt->execute([$tid]);
$avg = $stmt->fetch();

$pageTitle = 'My Reviews';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title">
      <h1>My Reviews</h1>
      <span class="rating-stars" style="font-size:1.2rem;">★ <?= number_format((float)$avg['a'], 1) ?> (<?= (int)$avg['c'] ?> reviews)</span>
    </div>
    <?php if (empty($reviews)): ?>
      <div class="empty-state"><p>No reviews yet.</p></div>
    <?php else: ?>
      <div class="grid grid-2">
        <?php foreach ($reviews as $r): ?>
          <div class="card">
            <strong><?= e($r['reviewer_name']) ?></strong> &middot; <span class="rating-stars">★ <?= (int)$r['rating'] ?></span>
            <p style="margin-top:8px;"><?= e($r['comment'] ?? '') ?></p>
            <p class="help-text"><?= formatDate($r['created_at']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
