<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT b.*, l.title AS listing_title, u.name AS student_name
    FROM bookings b JOIN skill_listings l ON l.id = b.listing_id JOIN users u ON u.id = b.student_id
    WHERE b.teacher_id = ? ORDER BY b.start_time DESC
");
$stmt->execute([$tid]);
$bookings = $stmt->fetchAll();

$pageTitle = 'My Bookings';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>My Bookings</h1></div>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>
    <?php if (empty($bookings)): ?>
      <div class="empty-state"><p>No bookings yet.</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Skill</th><th>Student</th><th>When</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td><?= e($b['listing_title']) ?></td>
              <td><?= e($b['student_name']) ?></td>
              <td><?= formatDateTime($b['start_time']) ?></td>
              <td><span class="badge badge-muted"><?= e($b['payment_type']) ?></span></td>
              <td><span class="badge <?= statusBadgeClass($b['status']) ?>"><?= e($b['status']) ?></span></td>
              <td style="white-space:nowrap;">
                <?php if ($b['status'] === 'pending'): ?>
                  <form method="POST" action="<?= BASE_URL ?>/teacher/booking_action.php" class="inline-form">
                    <input type="hidden" name="action" value="confirm"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                    <button class="btn btn-success btn-sm">Confirm</button>
                  </form>
                <?php endif; ?>
                <?php if (in_array($b['status'], ['confirmed','rescheduled'], true)): ?>
                  <a href="<?= BASE_URL ?>/shared/meeting.php?id=<?= (int)$b['id'] ?>" class="btn btn-primary btn-sm">Join</a>
                  <form method="POST" action="<?= BASE_URL ?>/teacher/booking_action.php" class="inline-form">
                    <input type="hidden" name="action" value="complete"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                    <button class="btn btn-outline btn-sm">Mark Completed</button>
                  </form>
                <?php endif; ?>
                <?php if (in_array($b['status'], ['pending','confirmed','rescheduled'], true)): ?>
                  <form method="POST" action="<?= BASE_URL ?>/teacher/booking_action.php" class="inline-form" onsubmit="return confirm('Cancel this booking?');">
                    <input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                    <button class="btn btn-danger btn-sm">Cancel</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
