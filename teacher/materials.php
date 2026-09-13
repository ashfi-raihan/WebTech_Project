<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT b.id, b.start_time, l.title AS listing_title, u.name AS student_name
    FROM bookings b JOIN skill_listings l ON l.id = b.listing_id JOIN users u ON u.id = b.student_id
    WHERE b.teacher_id = ? AND b.status IN ('confirmed','rescheduled','completed')
    ORDER BY b.start_time DESC
");
$stmt->execute([$tid]);
$completedBookings = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT m.*, b.id AS booking_id, l.title AS listing_title, u.name AS student_name
    FROM learning_materials m
    JOIN bookings b ON b.id = m.booking_id
    JOIN skill_listings l ON l.id = b.listing_id
    JOIN users u ON u.id = b.student_id
    WHERE m.teacher_id = ? ORDER BY m.created_at DESC
");
$stmt->execute([$tid]);
$materials = $stmt->fetchAll();

$pageTitle = 'Learning Materials';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>Learning Materials</h1></div>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>

    <div class="card" style="margin-bottom:24px;max-width:640px;">
      <h3>Share New Material</h3>
      <?php if (empty($completedBookings)): ?>
        <p class="help-text">You need at least one confirmed or completed session before sharing materials.</p>
      <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>/teacher/material_action.php">
          <input type="hidden" name="action" value="add">
          <div class="form-group">
            <label>Session</label>
            <select name="booking_id" required>
              <?php foreach ($completedBookings as $b): ?>
                <option value="<?= (int)$b['id'] ?>"><?= e($b['listing_title']) ?> - <?= e($b['student_name']) ?> (<?= formatDate($b['start_time']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" required placeholder="e.g. Week 1 Slides">
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Brief description"></textarea>
          </div>
          <div class="form-group">
            <label>Resource Link (optional)</label>
            <input type="url" name="file_url" placeholder="https://drive.google.com/...">
            <p class="help-text">Paste a link to a file hosted elsewhere (Drive, Dropbox, etc.)</p>
          </div>
          <button type="submit" class="btn btn-primary">Share Material</button>
        </form>
      <?php endif; ?>
    </div>

    <h3>Shared Materials</h3>
    <?php if (empty($materials)): ?>
      <p class="help-text">No materials shared yet.</p>
    <?php else: ?>
      <div class="grid grid-2">
        <?php foreach ($materials as $m): ?>
          <div class="card">
            <strong><?= e($m['title']) ?></strong>
            <p class="help-text"><?= e($m['listing_title']) ?> &middot; for <?= e($m['student_name']) ?></p>
            <p><?= e($m['description']) ?></p>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <?php if ($m['file_url']): ?><a href="<?= e($m['file_url']) ?>" target="_blank" class="btn btn-outline btn-sm">Open</a><?php else: ?><span></span><?php endif; ?>
              <form method="POST" action="<?= BASE_URL ?>/teacher/material_action.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="material_id" value="<?= (int)$m['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
