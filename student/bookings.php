<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$sid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT b.*, l.title AS listing_title, u.name AS teacher_name
    FROM bookings b JOIN skill_listings l ON l.id=b.listing_id JOIN users u ON u.id=b.teacher_id
    WHERE b.student_id = ? ORDER BY b.start_time DESC
");
$stmt->execute([$sid]);
$bookings = $stmt->fetchAll();

$listingsWithSlots = [];
$openSlotsStmt = $pdo->prepare("SELECT * FROM teacher_availability WHERE listing_id = ? AND is_booked = 0 AND start_time > NOW() ORDER BY start_time");
foreach ($bookings as $b) {
    if (!isset($listingsWithSlots[$b['listing_id']])) {
        $openSlotsStmt->execute([$b['listing_id']]);
        $listingsWithSlots[$b['listing_id']] = $openSlotsStmt->fetchAll();
    }
}

$pageTitle = 'My Bookings';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>My Bookings</h1></div>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>
    <?php if (empty($bookings)): ?>
      <div class="empty-state"><div class="icon">📅</div><p>No bookings yet. <a href="<?= BASE_URL ?>/listings.php">Browse skills</a> to book your first session!</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Skill</th><th>Teacher</th><th>When</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($bookings as $b): $slots = $listingsWithSlots[$b['listing_id']] ?? []; ?>
            <tr>
              <td><?= e($b['listing_title']) ?></td>
              <td><?= e($b['teacher_name']) ?></td>
              <td><?= formatDateTime($b['start_time']) ?></td>
              <td><span class="badge badge-muted"><?= e($b['payment_type']) ?></span></td>
              <td><span class="badge <?= statusBadgeClass($b['status']) ?>"><?= e($b['status']) ?></span></td>
              <td style="white-space:nowrap;">
                <?php if (in_array($b['status'], ['confirmed','rescheduled'], true)): ?>
                  <a href="<?= BASE_URL ?>/shared/meeting.php?id=<?= (int)$b['id'] ?>" class="btn btn-primary btn-sm">Join</a>
                <?php endif; ?>
                <?php if (in_array($b['status'], ['pending','confirmed'], true) && !empty($slots)): ?>
                  <details style="display:inline-block;">
                    <summary class="btn btn-outline btn-sm" style="cursor:pointer;">Reschedule</summary>
                    <form method="POST" action="<?= BASE_URL ?>/student/booking_action.php" style="margin-top:8px;">
                      <input type="hidden" name="action" value="reschedule">
                      <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                      <select name="new_availability_id" required>
                        <?php foreach ($slots as $s): ?><option value="<?= (int)$s['id'] ?>"><?= formatDateTime($s['start_time']) ?></option><?php endforeach; ?>
                      </select>
                      <button type="submit" class="btn btn-sm btn-primary">Confirm</button>
                    </form>
                  </details>
                <?php endif; ?>
                <?php if (in_array($b['status'], ['pending','confirmed','rescheduled'], true)): ?>
                  <form method="POST" action="<?= BASE_URL ?>/student/booking_action.php" class="inline-form" onsubmit="return confirm('Cancel this booking?');">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
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
