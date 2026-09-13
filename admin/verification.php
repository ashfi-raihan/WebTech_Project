<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$requests = $pdo->query("
    SELECT v.*, u.name AS user_name, u.email, u.role
    FROM verification_requests v
    JOIN users u ON u.id = v.user_id
    WHERE v.status = 'pending'
    ORDER BY v.created_at ASC
")->fetchAll();

$pageTitle = 'Verification Requests';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>Verification Requests</h1></div>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>
    <?php if (empty($requests)): ?>
      <div class="empty-state"><p>No pending verification requests.</p></div>
    <?php else: ?>
      <div class="grid grid-2">
        <?php foreach ($requests as $r): ?>
          <div class="card">
            <strong><?= e($r['user_name']) ?></strong> <span class="badge badge-muted"><?= e($r['role']) ?></span>
            <p class="help-text"><?= e($r['email']) ?></p>
            <p><?= e($r['document_info']) ?></p>
            <p class="help-text">Submitted <?= formatDate($r['created_at']) ?></p>
            <form method="POST" action="<?= BASE_URL ?>/admin/verification_action.php">
              <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
              <div class="form-group">
                <label>Admin Notes</label>
                <textarea name="admin_notes" placeholder="Reason / notes (optional)"></textarea>
              </div>
              <div style="display:flex;gap:8px;">
                <button type="submit" name="review_action" value="approve" class="btn btn-success btn-sm">Approve</button>
                <button type="submit" name="review_action" value="reject" class="btn btn-danger btn-sm">Reject</button>
              </div>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
