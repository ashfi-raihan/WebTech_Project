<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$user = currentUser();
$tid = $user['id'];

$stats = [
    'totalListings' => (int)scalar($pdo, 'SELECT COUNT(*) FROM skill_listings WHERE teacher_id = ?', [$tid]),
    'upcomingBookings' => (int)scalar($pdo, "SELECT COUNT(*) FROM bookings WHERE teacher_id = ? AND status IN ('confirmed','rescheduled') AND start_time > NOW()", [$tid]),
    'pendingBookings' => (int)scalar($pdo, "SELECT COUNT(*) FROM bookings WHERE teacher_id = ? AND status = 'pending'", [$tid]),
    'totalEarnings' => (float)scalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE payee_id = ? AND status='success'", [$tid]),
];

$stmt = $pdo->prepare("
    SELECT b.*, l.title AS listing_title, u.name AS student_name
    FROM bookings b JOIN skill_listings l ON l.id=b.listing_id JOIN users u ON u.id=b.student_id
    WHERE b.teacher_id = ? AND b.status IN ('confirmed','rescheduled','pending') AND b.start_time > NOW()
    ORDER BY b.start_time ASC LIMIT 5
");
$stmt->execute([$tid]);
$upcoming = $stmt->fetchAll();

$pageTitle = 'Teacher Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <h1>Welcome back, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h1>
    <div class="grid grid-4" style="margin:20px 0;">
      <div class="card stat-card"><div class="stat-value"><?= $stats['totalListings'] ?></div><div class="stat-label">Skill Listings</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['pendingBookings'] ?></div><div class="stat-label">Pending Bookings</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['upcomingBookings'] ?></div><div class="stat-label">Upcoming Sessions</div></div>
      <div class="card stat-card"><div class="stat-value">৳<?= number_format($stats['totalEarnings'], 2) ?></div><div class="stat-label">Total Earnings</div></div>
    </div>

    <div class="section-title"><h2>Upcoming & Pending Sessions</h2><a href="<?= BASE_URL ?>/teacher/bookings.php">View all</a></div>
    <?php if (empty($upcoming)): ?>
      <div class="empty-state"><p>No upcoming sessions. <a href="<?= BASE_URL ?>/teacher/skill_form.php">Create a listing</a> to attract students!</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Skill</th><th>Student</th><th>When</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($upcoming as $b): ?>
            <tr>
              <td><?= e($b['listing_title']) ?></td>
              <td><?= e($b['student_name']) ?></td>
              <td><?= formatDateTime($b['start_time']) ?></td>
              <td><span class="badge badge-info"><?= e($b['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="grid grid-2" style="margin-top:32px;">
      <a href="<?= BASE_URL ?>/teacher/skill_form.php" class="card card-hover"><h3>+ Create New Skill Listing</h3><p class="help-text">Share your expertise with students</p></a>
      <a href="<?= BASE_URL ?>/teacher/availability.php" class="card card-hover"><h3>📅 Manage Availability</h3><p class="help-text">Set your open time slots</p></a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
