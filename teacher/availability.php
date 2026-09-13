<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

$stmt = $pdo->prepare("SELECT id, title FROM skill_listings WHERE teacher_id = ? AND status='active'");
$stmt->execute([$tid]);
$listings = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT a.*, l.title AS listing_title
    FROM teacher_availability a JOIN skill_listings l ON l.id = a.listing_id
    WHERE a.teacher_id = ? ORDER BY a.start_time ASC
");
$stmt->execute([$tid]);
$slots = $stmt->fetchAll();

$pageTitle = 'Availability';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>Manage Availability</h1></div>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>

    <div class="card" style="margin-bottom:24px;max-width:640px;">
      <h3>Add New Time Slot</h3>
      <?php if (empty($listings)): ?>
        <p class="help-text">You need an active <a href="<?= BASE_URL ?>/teacher/skill_form.php">skill listing</a> before adding availability.</p>
      <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>/teacher/availability_action.php">
          <input type="hidden" name="action" value="create">
          <div class="form-group">
            <label>Skill Listing</label>
            <select name="listing_id" required>
              <?php foreach ($listings as $l): ?><option value="<?= (int)$l['id'] ?>"><?= e($l['title']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Start Time</label>
              <input type="datetime-local" name="start_time" required>
            </div>
            <div class="form-group">
              <label>End Time</label>
              <input type="datetime-local" name="end_time" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Add Slot</button>
        </form>
      <?php endif; ?>
    </div>

    <h3>Your Time Slots</h3>
    <?php if (empty($slots)): ?>
      <div class="empty-state"><p>No availability slots yet.</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Listing</th><th>Start</th><th>End</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($slots as $s): ?>
            <tr>
              <td><?= e($s['listing_title']) ?></td>
              <td><?= formatDateTime($s['start_time']) ?></td>
              <td><?= formatDateTime($s['end_time']) ?></td>
              <td><span class="badge <?= $s['is_booked'] ? 'badge-info' : 'badge-success' ?>"><?= $s['is_booked'] ? 'Booked' : 'Open' ?></span></td>
              <td>
                <?php if (!$s['is_booked']): ?>
                  <form method="POST" action="<?= BASE_URL ?>/teacher/availability_action.php" class="inline-form" onsubmit="return confirm('Remove this slot?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                  </form>
                <?php else: ?>
                  <span class="help-text">-</span>
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
