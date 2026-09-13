<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    redirect('/' . currentUser()['role'] . '/dashboard.php');
}

$featured = $pdo->query("
    SELECT l.*, s.name AS skill_name, u.name AS teacher_name,
           COALESCE(AVG(r.rating), 0) AS avg_rating
    FROM skill_listings l
    JOIN skills s ON s.id = l.skill_id
    JOIN users u ON u.id = l.teacher_id
    LEFT JOIN bookings b ON b.listing_id = l.id
    LEFT JOIN reviews r ON r.booking_id = b.id
    WHERE l.status = 'active'
    GROUP BY l.id
    ORDER BY l.created_at DESC
    LIMIT 6
")->fetchAll();

$stats = [
    'totalTeachers' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn(),
    'totalListings' => (int)$pdo->query("SELECT COUNT(*) FROM skill_listings WHERE status='active'")->fetchColumn(),
    'totalSessions' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn(),
];

$pageTitle = 'Skill-Sharing Management System';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <h1>Learn. Teach. Exchange Skills.</h1>
  <p>SkillShare connects students, teachers, and employers in one platform &mdash; book sessions, barter skills, and grow your career.</p>
  <div class="hero-buttons">
    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary" style="background:#fff;color:var(--primary);">Get Started</a>
    <a href="<?= BASE_URL ?>/listings.php" class="btn btn-outline">Browse Skills</a>
  </div>
</section>

<div class="container" style="padding:40px 20px;">
  <div class="grid grid-3" style="margin-bottom:48px;">
    <div class="card stat-card" style="text-align:center;"><div class="stat-value"><?= $stats['totalTeachers'] ?></div><div class="stat-label">Active Teachers</div></div>
    <div class="card stat-card" style="text-align:center;"><div class="stat-value"><?= $stats['totalListings'] ?></div><div class="stat-label">Skill Listings</div></div>
    <div class="card stat-card" style="text-align:center;"><div class="stat-value"><?= $stats['totalSessions'] ?></div><div class="stat-label">Sessions Completed</div></div>
  </div>

  <div class="section-title">
    <h2>Featured Skills</h2>
    <a href="<?= BASE_URL ?>/listings.php">View all &rarr;</a>
  </div>
  <?php if (empty($featured)): ?>
    <div class="empty-state"><p>No skill listings yet. Be the first teacher to create one!</p></div>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($featured as $l): ?>
        <a href="<?= BASE_URL ?>/listing.php?id=<?= (int)$l['id'] ?>" class="card card-hover listing-card">
          <h3><?= e($l['title']) ?></h3>
          <div class="meta">
            <span class="badge badge-info"><?= e($l['skill_name']) ?></span>
            <span class="badge badge-muted"><?= e($l['level']) ?></span>
          </div>
          <p style="color:var(--text-muted);font-size:0.9rem;">by <?= e($l['teacher_name']) ?></p>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;">
            <span class="price-tag"><?= $l['price'] > 0 ? '৳' . $l['price'] : 'Barter only' ?></span>
            <span class="rating-stars">★ <?= number_format((float)$l['avg_rating'], 1) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
