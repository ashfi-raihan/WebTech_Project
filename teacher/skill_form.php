<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

$listing = null;
if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM skill_listings WHERE id = ? AND teacher_id = ?');
    $stmt->execute([(int)$_GET['id'], $tid]);
    $listing = $stmt->fetch();
    if (!$listing) { http_response_code(404); include __DIR__ . '/../includes/error_404.php'; exit; }
}

$skills = $pdo->query('SELECT * FROM skills ORDER BY name')->fetchAll();

$pageTitle = $listing ? 'Edit Skill Listing' : 'Add New Skill Listing';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <h1><?= $listing ? 'Edit Skill Listing' : 'Add New Skill Listing' ?></h1>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>
    <div class="card" style="max-width:640px;">
      <form method="POST" action="<?= BASE_URL ?>/teacher/skill_action.php">
        <input type="hidden" name="action" value="<?= $listing ? 'update' : 'create' ?>">
        <?php if ($listing): ?><input type="hidden" name="listing_id" value="<?= (int)$listing['id'] ?>"><?php endif; ?>
        <div class="form-group">
          <label>Skill</label>
          <select name="skill_id" required>
            <option value="">Select a skill</option>
            <?php foreach ($skills as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= $listing && (int)$listing['skill_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Listing Title</label>
          <input type="text" name="title" required value="<?= e($listing['title'] ?? '') ?>" placeholder="e.g. Python for Beginners">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" placeholder="What will students learn?"><?= e($listing['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Level</label>
            <select name="level">
              <?php foreach (['beginner','intermediate','advanced','expert'] as $l): ?>
                <option value="<?= $l ?>" <?= $listing && $listing['level'] === $l ? 'selected' : '' ?>><?= ucfirst($l) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Price (৳)</label>
            <input type="number" step="0.01" min="0" name="price" required value="<?= e($listing['price'] ?? '0') ?>">
          </div>
          <div class="form-group">
            <label>Session Duration (minutes)</label>
            <input type="number" min="15" name="session_duration_minutes" required value="<?= e($listing['session_duration_minutes'] ?? '60') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="checkbox-row form-group">
            <input type="checkbox" name="allows_paid" id="allows_paid" <?= !$listing || $listing['allows_paid'] ? 'checked' : '' ?>>
            <label for="allows_paid" style="margin:0;">Accept Paid Bookings</label>
          </div>
          <div class="checkbox-row form-group">
            <input type="checkbox" name="allows_barter" id="allows_barter" <?= $listing && $listing['allows_barter'] ? 'checked' : '' ?>>
            <label for="allows_barter" style="margin:0;">Accept Barter Bookings</label>
          </div>
        </div>
        <?php if ($listing): ?>
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option value="active" <?= $listing['status'] === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= $listing['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Save Listing</button>
        <a href="<?= BASE_URL ?>/teacher/skills.php" class="btn btn-outline">Cancel</a>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
