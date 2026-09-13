<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$sid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT m.*, b.id AS booking_id, l.title AS listing_title, u.name AS teacher_name
    FROM learning_materials m
    JOIN bookings b ON b.id = m.booking_id
    JOIN skill_listings l ON l.id = b.listing_id
    JOIN users u ON u.id = b.teacher_id
    WHERE b.student_id = ? ORDER BY m.created_at DESC
");
$stmt->execute([$sid]);
$materials = $stmt->fetchAll();

$pageTitle = 'Learning Materials';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>Learning Materials</h1></div>
    <?php if (empty($materials)): ?>
      <div class="empty-state"><p>No learning materials have been shared with you yet.</p></div>
    <?php else: ?>
      <div class="grid grid-2">
        <?php foreach ($materials as $m): ?>
          <div class="card">
            <strong><?= e($m['title']) ?></strong>
            <p class="help-text"><?= e($m['listing_title']) ?> &middot; from <?= e($m['teacher_name']) ?></p>
            <p><?= e($m['description']) ?></p>
            <?php if ($m['file_url']): ?><a href="<?= e($m['file_url']) ?>" target="_blank" class="btn btn-outline btn-sm">Open Resource</a><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
