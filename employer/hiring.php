<?php
require_once __DIR__ . '/../config/config.php';
requireRole('employer');
$eid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT h.*, u.name AS candidate_name,
      (SELECT COUNT(*) FROM reviews r WHERE r.hiring_id = h.id AND r.reviewer_id = ?) AS reviewed
    FROM hiring_records h JOIN users u ON u.id = h.candidate_id
    WHERE h.employer_id = ? ORDER BY h.created_at DESC
");
$stmt->execute([$eid, $eid]);
$records = $stmt->fetchAll();

$pageTitle = 'Hiring & Engagements';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title">
      <h1>Hiring & Engagements</h1>
      <a href="<?= BASE_URL ?>/employer/find_talent.php" class="btn btn-primary">+ Find Talent</a>
    </div>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>
    <?php if (empty($records)): ?>
      <div class="empty-state"><p>No hiring engagements yet.</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Candidate</th><th>Position</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($records as $h): ?>
            <tr>
              <td><a href="<?= BASE_URL ?>/employer/candidate.php?id=<?= (int)$h['candidate_id'] ?>"><?= e($h['candidate_name']) ?></a></td>
              <td><?= e($h['position_title']) ?></td>
              <td>
                <form method="POST" action="<?= BASE_URL ?>/employer/hiring_action.php" style="display:inline-flex;gap:6px;align-items:center;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="hiring_id" value="<?= (int)$h['id'] ?>">
                  <select name="status" onchange="this.form.submit()">
                    <?php foreach (['contacted','interviewing','hired','declined','completed'] as $s): ?>
                      <option value="<?= $s ?>" <?= $h['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td><?= formatDate($h['created_at']) ?></td>
              <td>
                <?php if (in_array($h['status'], ['hired','completed'], true) && !$h['reviewed']): ?>
                  <details>
                    <summary class="btn btn-outline btn-sm" style="cursor:pointer;display:inline-block;">Leave Review</summary>
                    <form method="POST" action="<?= BASE_URL ?>/employer/review_action.php" style="margin-top:8px;min-width:220px;">
                      <input type="hidden" name="hiring_id" value="<?= (int)$h['id'] ?>">
                      <select name="rating" required style="margin-bottom:6px;">
                        <option value="5">★★★★★</option><option value="4">★★★★</option><option value="3">★★★</option><option value="2">★★</option><option value="1">★</option>
                      </select>
                      <textarea name="comment" placeholder="Comment" style="min-height:50px;margin-bottom:6px;"></textarea>
                      <button type="submit" class="btn btn-primary btn-sm">Submit</button>
                    </form>
                  </details>
                <?php elseif ($h['reviewed']): ?>
                  <span class="badge badge-success">Reviewed</span>
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
