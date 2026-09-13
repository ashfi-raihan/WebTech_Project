<?php
require_once __DIR__ . '/../config/config.php';
requireRole('employer');

$q = trim($_GET['q'] ?? '');
$skill = $_GET['skill'] ?? '';
$min_rating = $_GET['min_rating'] ?? '';
$verified_only = $_GET['verified_only'] ?? '';

$sql = "
    SELECT u.id, u.name, u.verification_status, p.headline, p.location,
      COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(DISTINCT r.id) AS review_count
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    LEFT JOIN reviews r ON r.reviewee_id = u.id
    LEFT JOIN user_skills us ON us.user_id = u.id
    LEFT JOIN skills s ON s.id = us.skill_id
    WHERE u.role = 'teacher' AND u.is_active = 1
";
$params = [];
if ($q !== '') { $sql .= " AND (u.name LIKE ? OR p.headline LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($skill !== '') { $sql .= " AND s.id = ?"; $params[] = $skill; }
if ($verified_only) { $sql .= " AND u.verification_status = 'approved'"; }
$sql .= " GROUP BY u.id";
if ($min_rating !== '') { $sql .= " HAVING avg_rating >= ?"; $params[] = $min_rating; }
$sql .= " ORDER BY avg_rating DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$candidates = $stmt->fetchAll();

$skills = $pdo->query('SELECT * FROM skills ORDER BY name')->fetchAll();

$pageTitle = 'Find Talent';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>Find Talent</h1></div>

    <form method="GET" action="<?= BASE_URL ?>/employer/find_talent.php" class="card" style="margin-bottom:24px;">
      <div class="form-row">
        <div class="form-group">
          <label>Search by Name</label>
          <input type="text" name="q" value="<?= e($q) ?>" placeholder="Name or headline">
        </div>
        <div class="form-group">
          <label>Skill</label>
          <select name="skill">
            <option value="">Any Skill</option>
            <?php foreach ($skills as $s): ?>
              <option value="<?= (int)$s['id'] ?>" <?= (string)$skill === (string)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Min Rating</label>
          <select name="min_rating">
            <option value="">Any</option>
            <?php foreach ([4,3,2,1] as $r): ?><option value="<?= $r ?>" <?= (string)$min_rating === (string)$r ? 'selected' : '' ?>><?= $r ?>+ stars</option><?php endforeach; ?>
          </select>
        </div>
        <div class="checkbox-row form-group" style="align-self:end;">
          <input type="checkbox" name="verified_only" id="vo" value="1" <?= $verified_only ? 'checked' : '' ?>>
          <label for="vo" style="margin:0;">Verified Only</label>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Search</button>
      <a href="<?= BASE_URL ?>/employer/find_talent.php" class="btn btn-outline">Reset</a>
    </form>

    <?php if (empty($candidates)): ?>
      <div class="empty-state"><p>No candidates match your search.</p></div>
    <?php else: ?>
      <div class="grid grid-3">
        <?php foreach ($candidates as $c): ?>
          <a href="<?= BASE_URL ?>/employer/candidate.php?id=<?= (int)$c['id'] ?>" class="card card-hover">
            <div style="display:flex;gap:12px;align-items:center;">
              <div class="avatar-circle"><?= e(mb_substr($c['name'], 0, 1)) ?></div>
              <div>
                <strong><?= e($c['name']) ?></strong>
                <p class="help-text" style="margin:0;"><?= e($c['headline'] ?? 'Teacher') ?></p>
              </div>
            </div>
            <div style="margin-top:12px;display:flex;justify-content:space-between;">
              <span class="rating-stars">★ <?= number_format((float)$c['avg_rating'], 1) ?> (<?= (int)$c['review_count'] ?>)</span>
              <?php if ($c['verification_status'] === 'approved'): ?><span class="badge badge-success">✓ Verified</span><?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
