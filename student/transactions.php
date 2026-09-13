<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$sid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT t.*, u.name AS payee_name, b.listing_id, l.title AS listing_title
    FROM transactions t
    JOIN users u ON u.id = t.payee_id
    LEFT JOIN bookings b ON b.id = t.booking_id
    LEFT JOIN skill_listings l ON l.id = b.listing_id
    WHERE t.payer_id = ? ORDER BY t.created_at DESC
");
$stmt->execute([$sid]);
$transactions = $stmt->fetchAll();

$pageTitle = 'Transaction History';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>Transaction History</h1></div>
    <?php if (empty($transactions)): ?>
      <div class="empty-state"><p>No transactions yet.</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Reference</th><th>Skill</th><th>Paid To</th><th>Amount</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($transactions as $t): ?>
            <tr>
              <td><?= e($t['reference']) ?></td>
              <td><?= e($t['listing_title'] ?? '-') ?></td>
              <td><?= e($t['payee_name']) ?></td>
              <td>৳<?= number_format((float)$t['amount'], 2) ?></td>
              <td><?= e($t['type']) ?></td>
              <td><span class="badge badge-success"><?= e($t['status']) ?></span></td>
              <td><?= formatDateTime($t['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
