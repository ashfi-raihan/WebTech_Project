<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT be.*, u.name AS sender_name, so.name AS offered_skill_name, sr.name AS requested_skill_name
    FROM barter_exchanges be
    JOIN users u ON u.id = be.sender_id
    JOIN skills so ON so.id = be.offered_skill_id
    JOIN skills sr ON sr.id = be.requested_skill_id
    WHERE be.receiver_id = ? ORDER BY be.created_at DESC
");
$stmt->execute([$tid]);
$incoming = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT be.*, u.name AS receiver_name, so.name AS offered_skill_name, sr.name AS requested_skill_name
    FROM barter_exchanges be
    JOIN users u ON u.id = be.receiver_id
    JOIN skills so ON so.id = be.offered_skill_id
    JOIN skills sr ON sr.id = be.requested_skill_id
    WHERE be.sender_id = ? ORDER BY be.created_at DESC
");
$stmt->execute([$tid]);
$outgoing = $stmt->fetchAll();

$pageTitle = 'Barter Exchanges';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>Barter Exchanges</h1></div>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>

    <h2>Incoming Offers</h2>
    <?php if (empty($incoming)): ?><p class="help-text">No incoming offers.</p><?php endif; ?>
    <div class="grid grid-2" style="margin-bottom:24px;">
      <?php foreach ($incoming as $o): ?>
        <div class="card">
          <p><strong><?= e($o['sender_name']) ?></strong> offers <strong><?= e($o['offered_skill_name']) ?></strong> for your <strong><?= e($o['requested_skill_name']) ?></strong></p>
          <?php if ($o['message']): ?><p class="help-text">"<?= e($o['message']) ?>"</p><?php endif; ?>
          <span class="badge badge-warning"><?= e($o['status']) ?></span>
          <?php if ($o['status'] === 'pending'): ?>
            <div style="margin-top:10px;display:flex;gap:8px;">
              <form method="POST" action="<?= BASE_URL ?>/teacher/barter_action.php">
                <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="respond_action" value="accept">
                <button class="btn btn-success btn-sm">Accept</button>
              </form>
              <form method="POST" action="<?= BASE_URL ?>/teacher/barter_action.php">
                <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="respond_action" value="reject">
                <button class="btn btn-danger btn-sm">Reject</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <h2>Outgoing Offers</h2>
    <?php if (empty($outgoing)): ?><p class="help-text">No outgoing offers.</p><?php endif; ?>
    <div class="grid grid-2">
      <?php foreach ($outgoing as $o): ?>
        <div class="card">
          <p>You offered <strong><?= e($o['offered_skill_name']) ?></strong> to <strong><?= e($o['receiver_name']) ?></strong> for <strong><?= e($o['requested_skill_name']) ?></strong></p>
          <span class="badge badge-warning"><?= e($o['status']) ?></span>
          <?php if ($o['status'] === 'pending'): ?>
            <form method="POST" action="<?= BASE_URL ?>/teacher/barter_action.php" style="margin-top:10px;">
              <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="respond_action" value="cancel">
              <button class="btn btn-outline btn-sm">Cancel Offer</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
