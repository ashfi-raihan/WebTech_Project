<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$sid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT b.*, l.title AS listing_title, u.name AS teacher_name
    FROM bookings b JOIN skill_listings l ON l.id=b.listing_id JOIN users u ON u.id=b.teacher_id
    WHERE b.student_id = ? AND b.status NOT IN ('cancelled')
    ORDER BY b.start_time ASC
");
$stmt->execute([$sid]);
$events = $stmt->fetchAll();

$now = time();
$upcoming = array_filter($events, fn($e) => strtotime($e['start_time']) > $now);
$past = array_filter($events, fn($e) => strtotime($e['start_time']) <= $now);

$pageTitle = 'My Calendar';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>My Calendar</h1></div>
    <?php if (empty($events)): ?>
      <div class="empty-state"><p>No scheduled sessions yet.</p></div>
    <?php else: ?>
      <h2>Upcoming</h2>
      <div class="grid grid-3" style="margin-bottom:32px;">
        <?php if (empty($upcoming)): ?><p class="help-text">No upcoming sessions.</p><?php endif; ?>
        <?php foreach ($upcoming as $e): ?>
          <div class="card">
            <strong><?= e($e['listing_title']) ?></strong>
            <p class="help-text">with <?= e($e['teacher_name']) ?></p>
            <p><?= formatDateTime($e['start_time']) ?></p>
            <span class="badge badge-info"><?= e($e['status']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <h2>Past</h2>
      <div class="grid grid-3">
        <?php if (empty($past)): ?><p class="help-text">No past sessions.</p><?php endif; ?>
        <?php foreach ($past as $e): ?>
          <div class="card" style="opacity:0.85;">
            <strong><?= e($e['listing_title']) ?></strong>
            <p class="help-text">with <?= e($e['teacher_name']) ?></p>
            <p><?= formatDateTime($e['start_time']) ?></p>
            <span class="badge badge-muted"><?= e($e['status']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
