<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$skills = $pdo->query("
    SELECT s.*, c.name AS category_name,
      (SELECT COUNT(*) FROM skill_listings l WHERE l.skill_id = s.id) AS listing_count
    FROM skills s LEFT JOIN categories c ON c.id = s.category_id ORDER BY s.name
")->fetchAll();

$pageTitle = 'Manage Skills & Categories';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>Skills & Categories</h1></div>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>

    <div class="grid grid-2" style="align-items:start;">
      <div>
        <div class="card" style="margin-bottom:20px;">
          <h3>Add Category</h3>
          <form method="POST" action="<?= BASE_URL ?>/admin/skill_action.php">
            <input type="hidden" name="action" value="create_category">
            <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
            <div class="form-group"><label>Description</label><input type="text" name="description"></div>
            <button type="submit" class="btn btn-primary btn-sm">Add Category</button>
          </form>
        </div>
        <div class="card">
          <h3>Categories</h3>
          <?php foreach ($categories as $c): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border);padding:8px 0;">
              <span><?= e($c['name']) ?></span>
              <form method="POST" action="<?= BASE_URL ?>/admin/skill_action.php" onsubmit="return confirm('Delete this category?');">
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <div class="card" style="margin-bottom:20px;">
          <h3>Add Skill</h3>
          <form method="POST" action="<?= BASE_URL ?>/admin/skill_action.php">
            <input type="hidden" name="action" value="create_skill">
            <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
            <div class="form-group">
              <label>Category</label>
              <select name="category_id">
                <option value="">None</option>
                <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Add Skill</button>
          </form>
        </div>
        <div class="table-wrap card" style="padding:0;">
          <table>
            <thead><tr><th>Skill</th><th>Category</th><th>Listings</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($skills as $s): ?>
              <tr>
                <td><?= e($s['name']) ?></td>
                <td><?= e($s['category_name'] ?? '-') ?></td>
                <td><?= (int)$s['listing_count'] ?></td>
                <td>
                  <form method="POST" action="<?= BASE_URL ?>/admin/skill_action.php" onsubmit="return confirm('Delete this skill?');">
                    <input type="hidden" name="action" value="delete_skill">
                    <input type="hidden" name="skill_id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
