<?php
require_once __DIR__ . '/../config/config.php';
requireRole('employer');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.verification_status, u.created_at, p.*
    FROM users u LEFT JOIN user_profiles p ON p.user_id = u.id
    WHERE u.id = ? AND u.role = 'teacher'
");
$stmt->execute([$id]);
$candidate = $stmt->fetch();

if (!$candidate) { http_response_code(404); include __DIR__ . '/../includes/error_404.php'; exit; }

$stmt = $pdo->prepare("SELECT us.*, s.name AS skill_name FROM user_skills us JOIN skills s ON s.id = us.skill_id WHERE us.user_id = ?");
$stmt->execute([$id]);
$skills = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM skill_listings WHERE teacher_id = ? AND status='active'");
$stmt->execute([$id]);
$listings = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT r.*, u.name AS reviewer_name FROM reviews r JOIN users u ON u.id = r.reviewer_id WHERE r.reviewee_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT COALESCE(AVG(rating),0) a, COUNT(*) c FROM reviews WHERE reviewee_id = ?');
$stmt->execute([$id]);
$ratingRow = $stmt->fetch();

$pageTitle = $candidate['name'];
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="card" style="margin-bottom:24px;display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
      <div class="avatar-circle" style="width:72px;height:72px;font-size:1.7rem;"><?= e(mb_substr($candidate['name'], 0, 1)) ?></div>
      <div>
        <h1 style="margin-bottom:2px;"><?= e($candidate['name']) ?></h1>
        <p class="help-text" style="margin:0;"><?= e($candidate['headline'] ?? 'Teacher') ?><?= $candidate['location'] ? ' · ' . e($candidate['location']) : '' ?></p>
        <div style="margin-top:6px;">
          <?php if ($candidate['verification_status'] === 'approved'): ?><span class="badge badge-success">✓ Verified</span><?php endif; ?>
          <span class="badge badge-info">★ <?= number_format((float)$ratingRow['a'], 1) ?> (<?= (int)$ratingRow['c'] ?> reviews)</span>
        </div>
      </div>
    </div>

    <div class="grid grid-2" style="align-items:start;">
      <div>
        <div class="card" style="margin-bottom:20px;">
          <h3>About</h3>
          <p><?= e($candidate['bio'] ?? 'No bio provided.') ?></p>
          <?php if ($candidate['education']): ?><p><strong>Education:</strong> <?= e($candidate['education']) ?></p><?php endif; ?>
          <?php if ($candidate['experience']): ?><p><strong>Experience:</strong> <?= e($candidate['experience']) ?></p><?php endif; ?>
        </div>

        <div class="card" style="margin-bottom:20px;">
          <h3>Skills</h3>
          <?php if (empty($skills)): ?><p class="help-text">No skills listed.</p><?php endif; ?>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ($skills as $s): ?><span class="badge badge-info"><?= e($s['skill_name']) ?> (<?= e($s['proficiency']) ?>)</span><?php endforeach; ?>
          </div>
        </div>

        <div class="card" style="margin-bottom:20px;">
          <h3>Active Skill Listings</h3>
          <?php if (empty($listings)): ?><p class="help-text">No active listings.</p><?php endif; ?>
          <?php foreach ($listings as $l): ?>
            <p><a href="<?= BASE_URL ?>/listing.php?id=<?= (int)$l['id'] ?>"><?= e($l['title']) ?></a> - <?= $l['price'] > 0 ? '৳' . $l['price'] : 'Barter' ?></p>
          <?php endforeach; ?>
        </div>

        <div class="card">
          <h3>Reviews</h3>
          <?php if (empty($reviews)): ?><p class="help-text">No reviews yet.</p><?php endif; ?>
          <?php foreach ($reviews as $r): ?>
            <div style="border-bottom:1px solid var(--border);padding:10px 0;">
              <strong><?= e($r['reviewer_name']) ?></strong> &middot; <span class="rating-stars">★ <?= (int)$r['rating'] ?></span>
              <p style="margin:4px 0 0;"><?= e($r['comment']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <h3>Contact / Hire</h3>
        <form method="POST" action="<?= BASE_URL ?>/employer/hiring_action.php">
          <input type="hidden" name="action" value="create">
          <input type="hidden" name="candidate_id" value="<?= (int)$candidate['id'] ?>">
          <div class="form-group">
            <label>Position Title</label>
            <input type="text" name="position_title" required placeholder="e.g. Part-time Mentor">
          </div>
          <div class="form-group">
            <label>Message</label>
            <textarea name="message" placeholder="Tell them about the opportunity..."></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Contact Candidate</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
