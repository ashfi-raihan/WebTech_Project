<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$reports = $pdo->query("
    SELECT r.*, u.name AS reporter_name
    FROM reports r JOIN users u ON u.id = r.reporter_id
    ORDER BY r.created_at DESC
")->fetchAll();

$moderationHistory = $pdo->query("
    SELECT m.*, r.reason, r.target_type, a.name AS admin_name
    FROM moderation_records m
    JOIN reports r ON r.id = m.report_id
    JOIN users a ON a.id = m.admin_id
    ORDER BY m.created_at DESC
")->fetchAll();

$openReports = array_filter($reports, fn($r) => !in_array($r['status'], ['resolved', 'dismissed'], true));

$pageTitle = 'Reports & Moderation';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>Reports & Moderation</h1></div>

    <h2>Open / Under Review</h2>
    <?php if (empty($openReports)): ?>
      <p class="help-text">No open reports.</p>
    <?php else: ?>
      <div class="grid grid-2" style="margin-bottom:32px;">
        <?php foreach ($openReports as $r): ?>
          <div class="card">
            <span class="badge badge-info"><?= e($r['target_type']) ?> #<?= (int)$r['target_id'] ?></span>
            <span class="badge badge-warning"><?= e($r['status']) ?></span>
            <p style="margin-top:8px;">Reported by <strong><?= e($r['reporter_name']) ?></strong></p>
            <p><?= e($r['reason']) ?></p>
            <form method="POST" action="<?= BASE_URL ?>/admin/report_action.php">
              <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
              <div class="form-group">
                <label>Action Notes</label>
                <textarea name="notes" placeholder="Describe the action taken..."></textarea>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Action</label>
                  <select name="mod_action">
                    <option value="reviewed_no_action">No action needed</option>
                    <?php if ($r['target_type'] === 'listing'): ?><option value="listing_removed">Remove listing</option><?php endif; ?>
                    <?php if ($r['target_type'] === 'user'): ?><option value="user_deactivated">Deactivate user</option><?php endif; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Mark As</label>
                  <select name="status">
                    <option value="resolved">Resolved</option>
                    <option value="dismissed">Dismissed</option>
                  </select>
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-sm">Submit</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h2>Moderation History</h2>
    <?php if (empty($moderationHistory)): ?>
      <p class="help-text">No moderation actions yet.</p>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Reason</th><th>Target</th><th>Action</th><th>Admin</th><th>Notes</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($moderationHistory as $m): ?>
            <tr>
              <td><?= e($m['reason']) ?></td>
              <td><?= e($m['target_type']) ?></td>
              <td><span class="badge badge-muted"><?= e($m['action']) ?></span></td>
              <td><?= e($m['admin_name']) ?></td>
              <td><?= e($m['notes'] ?? '-') ?></td>
              <td><?= formatDate($m['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
