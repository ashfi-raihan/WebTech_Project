<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$listings = $pdo->query("
    SELECT l.*, s.name AS skill_name, u.name AS teacher_name
    FROM skill_listings l JOIN skills s ON s.id = l.skill_id JOIN users u ON u.id = l.teacher_id
    ORDER BY l.created_at DESC
")->fetchAll();

$pageTitle = 'All Skill Listings';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>All Skill Listings</h1></div>
    <?php if (empty($listings)): ?>
      <div class="empty-state"><p>No listings yet.</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Title</th><th>Skill</th><th>Teacher</th><th>Price</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($listings as $l): ?>
            <tr>
              <td><a href="<?= BASE_URL ?>/listing.php?id=<?= (int)$l['id'] ?>"><?= e($l['title']) ?></a></td>
              <td><?= e($l['skill_name']) ?></td>
              <td><?= e($l['teacher_name']) ?></td>
              <td><?= $l['price'] > 0 ? '৳' . $l['price'] : 'Barter' ?></td>
              <td><span class="badge <?= statusBadgeClass($l['status']) ?>"><?= e($l['status']) ?></span></td>
              <td><?= formatDate($l['created_at']) ?></td>
              <td>
                <?php if ($l['status'] !== 'removed'): ?>
                  <form method="POST" action="<?= BASE_URL ?>/admin/listing_action.php" onsubmit="return confirm('Remove this listing?');">
                    <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Remove</button>
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
