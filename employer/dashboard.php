<?php
require_once __DIR__ . '/../config/config.php';
requireRole('employer');
$user = currentUser();
$eid = $user['id'];

$stats = [
    'totalHires' => (int)scalar($pdo, "SELECT COUNT(*) FROM hiring_records WHERE employer_id=? AND status IN ('hired','completed')", [$eid]),
    'activeEngagements' => (int)scalar($pdo, "SELECT COUNT(*) FROM hiring_records WHERE employer_id=? AND status IN ('contacted','interviewing')", [$eid]),
    'reviewsGiven' => (int)scalar($pdo, "SELECT COUNT(*) FROM reviews WHERE reviewer_id=? AND hiring_id IS NOT NULL", [$eid]),
];

$stmt = $pdo->prepare("
    SELECT h.*, u.name AS candidate_name
    FROM hiring_records h JOIN users u ON u.id = h.candidate_id
    WHERE h.employer_id = ? ORDER BY h.created_at DESC LIMIT 5
");
$stmt->execute([$eid]);
$recent = $stmt->fetchAll();

$pageTitle = 'Employer Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <h1>Welcome back, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h1>
    <div class="grid grid-3" style="margin:20px 0;">
      <div class="card stat-card"><div class="stat-value"><?= $stats['totalHires'] ?></div><div class="stat-label">Total Hires</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['activeEngagements'] ?></div><div class="stat-label">Active Engagements</div></div>
      <div class="card stat-card"><div class="stat-value"><?= $stats['reviewsGiven'] ?></div><div class="stat-label">Reviews Given</div></div>
    </div>

    <div class="section-title"><h2>Recent Engagements</h2><a href="<?= BASE_URL ?>/employer/hiring.php">View all</a></div>
    <?php if (empty($recent)): ?>
      <div class="empty-state"><p>No hiring activity yet. <a href="<?= BASE_URL ?>/employer/find_talent.php">Find talent</a> to get started!</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Candidate</th><th>Position</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $h): ?>
            <tr>
              <td><?= e($h['candidate_name']) ?></td>
              <td><?= e($h['position_title']) ?></td>
              <td><span class="badge badge-info"><?= e($h['status']) ?></span></td>
              <td><?= formatDate($h['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/employer/find_talent.php" class="card card-hover" style="display:block;margin-top:24px;">
      <h3>🔍 Find Skilled Talent</h3>
      <p class="help-text">Search verified teachers and skilled users to hire</p>
    </a>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
