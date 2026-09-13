<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT l.*, s.name AS skill_name,
      (SELECT COUNT(*) FROM bookings b WHERE b.listing_id = l.id) AS booking_count
    FROM skill_listings l JOIN skills s ON s.id = l.skill_id
    WHERE l.teacher_id = ? ORDER BY l.created_at DESC
");
$stmt->execute([$tid]);
$listings = $stmt->fetchAll();

$pageTitle = 'My Skills';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title">
      <h1>My Skill Listings</h1>
      <a href="<?= BASE_URL ?>/teacher/skill_form.php" class="btn btn-primary">+ Add New Listing</a>
    </div>
    <?php if (empty($listings)): ?>
      <div class="empty-state"><p>You haven't created any skill listings yet.</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Title</th><th>Skill</th><th>Price</th><th>Bookings</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($listings as $l): ?>
            <tr>
              <td><?= e($l['title']) ?></td>
              <td><?= e($l['skill_name']) ?></td>
              <td><?= $l['price'] > 0 ? '৳' . $l['price'] : 'Barter' ?></td>
              <td><?= (int)$l['booking_count'] ?></td>
              <td><span class="badge <?= statusBadgeClass($l['status']) ?>"><?= e($l['status']) ?></span></td>
              <td style="white-space:nowrap;">
                <a href="<?= BASE_URL ?>/teacher/skill_form.php?id=<?= (int)$l['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                <form method="POST" action="<?= BASE_URL ?>/teacher/skill_action.php" class="inline-form" onsubmit="return confirm('Remove this listing?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
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
