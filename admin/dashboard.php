<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$stats = [
    'totalUsers' => (int)scalar($pdo, 'SELECT COUNT(*) FROM users'),
    'students' => (int)scalar($pdo, "SELECT COUNT(*) FROM users WHERE role='student'"),
    'teachers' => (int)scalar($pdo, "SELECT COUNT(*) FROM users WHERE role='teacher'"),
    'employers' => (int)scalar($pdo, "SELECT COUNT(*) FROM users WHERE role='employer'"),
    'totalListings' => (int)scalar($pdo, "SELECT COUNT(*) FROM skill_listings WHERE status='active'"),
    'totalBookings' => (int)scalar($pdo, 'SELECT COUNT(*) FROM bookings'),
    'completedBookings' => (int)scalar($pdo, "SELECT COUNT(*) FROM bookings WHERE status='completed'"),
    'pendingVerifications' => (int)scalar($pdo, "SELECT COUNT(*) FROM verification_requests WHERE status='pending'"),
    'openReports' => (int)scalar($pdo, "SELECT COUNT(*) FROM reports WHERE status='open'"),
    'totalRevenue' => (float)scalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status='success' AND type='session_payment'"),
];

$recentUsers = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();
$recentBookings = $pdo->query("
    SELECT b.*, l.title AS listing_title, s.name AS student_name, t.name AS teacher_name
    FROM bookings b
    JOIN skill_listings l ON l.id = b.listing_id
    JOIN users s ON s.id = b.student_id
    JOIN users t ON t.id = b.teacher_id
    ORDER BY b.created_at DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <h1>Admin Dashboard</h1>
    <div class="grid grid-4" style="margin:20px 0;">
      <div class="card stat-card"><div class="stat-value"><?= $stats['totalUsers'] ?></div><div class="stat-label">Total Users</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['totalListings'] ?></div><div class="stat-label">Active Listings</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['totalBookings'] ?></div><div class="stat-label">Total Bookings</div></div>
      <div class="card stat-card"><div class="stat-value">৳<?= number_format($stats['totalRevenue'], 2) ?></div><div class="stat-label">Total Revenue</div></div>
    </div>
    <div class="grid grid-4" style="margin-bottom:32px;">
      <div class="card stat-card"><div class="stat-value"><?= $stats['students'] ?></div><div class="stat-label">Students</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['teachers'] ?></div><div class="stat-label">Teachers</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['employers'] ?></div><div class="stat-label">Employers</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['completedBookings'] ?></div><div class="stat-label">Completed Sessions</div></div>
    </div>

    <div class="grid grid-2" style="margin-bottom:32px;">
      <a href="<?= BASE_URL ?>/admin/verification.php" class="card card-hover">
        <h3>🛡️ Pending Verifications</h3>
        <div class="stat-value"><?= $stats['pendingVerifications'] ?></div>
      </a>
      <a href="<?= BASE_URL ?>/admin/reports.php" class="card card-hover">
        <h3>🚩 Open Reports</h3>
        <div class="stat-value"><?= $stats['openReports'] ?></div>
      </a>
    </div>

    <div class="grid grid-2">
      <div>
        <div class="section-title"><h2>Recent Users</h2><a href="<?= BASE_URL ?>/admin/users.php">View all</a></div>
        <div class="table-wrap card" style="padding:0;">
          <table>
            <thead><tr><th>Name</th><th>Role</th><th>Joined</th></tr></thead>
            <tbody>
            <?php foreach ($recentUsers as $u): ?>
              <tr><td><?= e($u['name']) ?></td><td><span class="badge badge-muted"><?= e($u['role']) ?></span></td><td><?= formatDate($u['created_at']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div>
        <div class="section-title"><h2>Recent Bookings</h2></div>
        <div class="table-wrap card" style="padding:0;">
          <table>
            <thead><tr><th>Skill</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentBookings as $b): ?>
              <tr><td><?= e($b['listing_title']) ?></td><td><span class="badge badge-info"><?= e($b['status']) ?></span></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
