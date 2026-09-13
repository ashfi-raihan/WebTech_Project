<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$transactions = $pdo->query("
    SELECT t.*, p.name AS payer_name, e.name AS payee_name
    FROM transactions t JOIN users p ON p.id = t.payer_id JOIN users e ON e.id = t.payee_id
    ORDER BY t.created_at DESC
")->fetchAll();

$totalRevenue = (float)scalar($pdo, "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status='success'");

$pageTitle = 'All Transactions';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title">
      <h1>All Transactions</h1>
      <span class="badge badge-success" style="font-size:0.95rem;">Total Revenue: ৳<?= number_format($totalRevenue, 2) ?></span>
    </div>
    <?php if (empty($transactions)): ?>
      <div class="empty-state"><p>No transactions yet.</p></div>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Reference</th><th>Payer</th><th>Payee</th><th>Amount</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($transactions as $t): ?>
            <tr>
              <td><?= e($t['reference']) ?></td>
              <td><?= e($t['payer_name']) ?></td>
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
