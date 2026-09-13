<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$user = currentUser();
$sid = $user['id'];

$stats = [
    'upcomingBookings' => (int)scalar($pdo, "SELECT COUNT(*) FROM bookings WHERE student_id=? AND status IN ('confirmed','rescheduled') AND start_time > NOW()", [$sid]),
    'completedSessions' => (int)scalar($pdo, "SELECT COUNT(*) FROM bookings WHERE student_id=? AND status='completed'", [$sid]),
    'wishlistCount' => (int)scalar($pdo, "SELECT COUNT(*) FROM wishlists WHERE student_id=?", [$sid]),
    'totalSpent' => (float)scalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE payer_id=? AND status='success'", [$sid]),
];

$stmt = $pdo->prepare("
    SELECT b.*, l.title AS listing_title, u.name AS teacher_name
    FROM bookings b JOIN skill_listings l ON l.id=b.listing_id JOIN users u ON u.id=b.teacher_id
    WHERE b.student_id=? AND b.status IN ('confirmed','rescheduled','pending') AND b.start_time > NOW()
    ORDER BY b.start_time ASC LIMIT 5
");
$stmt->execute([$sid]);
$upcoming = $stmt->fetchAll();

$recommended = $pdo->query("
    SELECT l.*, s.name AS skill_name, u.name AS teacher_name
    FROM skill_listings l JOIN skills s ON s.id=l.skill_id JOIN users u ON u.id=l.teacher_id
    WHERE l.status='active' ORDER BY l.created_at DESC LIMIT 4
")->fetchAll();

$pageTitle = 'Student Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <h1>Welcome back, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h1>
    <div class="grid grid-4" style="margin:20px 0;">
      <div class="card stat-card"><div class="stat-value"><?= $stats['upcomingBookings'] ?></div><div class="stat-label">Upcoming Sessions</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['completedSessions'] ?></div><div class="stat-label">Completed Sessions</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['wishlistCount'] ?></div><div class="stat-label">Wishlist Items</div></div>
      <div class="card stat-card"><div class="stat-value">৳<?= number_format($stats['totalSpent'], 2) ?></div><div class="stat-label">Total Spent</div></div>
    </div>

    <div class="section-title"><h2>Upcoming Sessions</h2><a href="<?= BASE_URL ?>/student/bookings.php">View all</a></div>
    <?php if (empty($upcoming)): ?>
      <div class="empty-state"><p>No upcoming sessions. <a href="<?= BASE_URL ?>/listings.php">Browse skills</a> to book one!</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Skill</th><th>Teacher</th><th>When</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($upcoming as $b): ?>
            <tr>
              <td><?= e($b['listing_title']) ?></td>
              <td><?= e($b['teacher_name']) ?></td>
              <td><?= formatDateTime($b['start_time']) ?></td>
              <td><span class="badge badge-info"><?= e($b['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="section-title" style="margin-top:32px;"><h2>Recommended For You</h2><a href="<?= BASE_URL ?>/listings.php">Browse all</a></div>
    <div class="grid grid-4">
      <?php foreach ($recommended as $l): ?>
        <a href="<?= BASE_URL ?>/listing.php?id=<?= (int)$l['id'] ?>" class="card card-hover listing-card">
          <h3 style="font-size:1rem;"><?= e($l['title']) ?></h3>
          <p style="color:var(--text-muted);font-size:0.85rem;">by <?= e($l['teacher_name']) ?></p>
          <span class="price-tag"><?= $l['price'] > 0 ? '৳' . $l['price'] : 'Barter' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
