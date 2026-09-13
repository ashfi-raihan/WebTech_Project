<?php
require_once __DIR__ . '/../config/config.php';
requireRole('employer');
$eid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT r.*, u.name AS candidate_name FROM reviews r JOIN users u ON u.id = r.reviewee_id
    WHERE r.reviewer_id = ? AND r.hiring_id IS NOT NULL ORDER BY r.created_at DESC
");
$stmt->execute([$eid]);
$myReviews = $stmt->fetchAll();

$pageTitle = 'My Reviews';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>My Reviews</h1></div>
    <?php if (empty($myReviews)): ?>
      <div class="empty-state"><p>You haven't reviewed any candidates yet.</p></div>
    <?php else: ?>
      <div class="grid grid-2">
        <?php foreach ($myReviews as $r): ?>
          <div class="card">
            <strong><?= e($r['candidate_name']) ?></strong> &middot; <span class="rating-stars">★ <?= (int)$r['rating'] ?></span>
            <p style="margin-top:8px;"><?= e($r['comment'] ?? '') ?></p>
            <p class="help-text"><?= formatDate($r['created_at']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
