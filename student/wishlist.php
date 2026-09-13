<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$sid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT w.id AS wishlist_id, l.*, s.name AS skill_name, u.name AS teacher_name
    FROM wishlists w
    JOIN skill_listings l ON l.id = w.listing_id
    JOIN skills s ON s.id = l.skill_id
    JOIN users u ON u.id = l.teacher_id
    WHERE w.student_id = ? ORDER BY w.created_at DESC
");
$stmt->execute([$sid]);
$items = $stmt->fetchAll();

$pageTitle = 'My Wishlist';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>My Wishlist</h1></div>
    <?php if (empty($items)): ?>
      <div class="empty-state"><div class="icon">💙</div><p>Your wishlist is empty. <a href="<?= BASE_URL ?>/listings.php">Browse skills</a> to add some!</p></div>
    <?php else: ?>
      <div class="grid grid-3">
        <?php foreach ($items as $l): ?>
          <div class="card listing-card">
            <a href="<?= BASE_URL ?>/listing.php?id=<?= (int)$l['id'] ?>"><h3><?= e($l['title']) ?></h3></a>
            <p style="color:var(--text-muted);font-size:0.9rem;">by <?= e($l['teacher_name']) ?> &middot; <?= e($l['skill_name']) ?></p>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;">
              <span class="price-tag"><?= $l['price'] > 0 ? '৳' . $l['price'] : 'Barter' ?></span>
              <form method="POST" action="<?= BASE_URL ?>/student/wishlist_action.php">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>">
                <button type="submit" class="btn btn-outline btn-sm">Remove</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
