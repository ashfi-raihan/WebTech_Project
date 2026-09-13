<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

$sql = 'SELECT id, name, email, role, phone, is_active, verification_status, created_at FROM users WHERE 1=1';
$params = [];
if ($q !== '') { $sql .= ' AND (name LIKE ? OR email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($role !== '') { $sql .= ' AND role = ?'; $params[] = $role; }
if ($status === 'active') $sql .= ' AND is_active = 1';
elseif ($status === 'inactive') $sql .= ' AND is_active = 0';
$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>Manage Users</h1></div>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>

    <form method="GET" action="<?= BASE_URL ?>/admin/users.php" class="card" style="margin-bottom:24px;">
      <div class="form-row">
        <div class="form-group"><label>Search</label><input type="text" name="q" value="<?= e($q) ?>" placeholder="Name or email"></div>
        <div class="form-group">
          <label>Role</label>
          <select name="role">
            <option value="">All Roles</option>
            <?php foreach (['student','teacher','employer','admin'] as $r): ?><option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="">Any Status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline">Reset</a>
    </form>

    <div class="table-wrap card" style="padding:0;">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Verification</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><span class="badge badge-muted"><?= e($u['role']) ?></span></td>
            <td><span class="badge <?= statusBadgeClass($u['verification_status']) ?>"><?= e($u['verification_status']) ?></span></td>
            <td><span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-danger' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td><?= formatDate($u['created_at']) ?></td>
            <td>
              <?php if ($u['role'] !== 'admin'): ?>
                <form method="POST" action="<?= BASE_URL ?>/admin/user_action.php" class="inline-form" onsubmit="return confirm('Change user active status?');">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                </form>
              <?php else: ?>
                <span class="help-text">-</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
