<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$sid = currentUser()['id'];

$stmt = $pdo->prepare("
    SELECT b.*, l.title AS listing_title, u.name AS teacher_name
    FROM bookings b
    JOIN skill_listings l ON l.id = b.listing_id
    JOIN users u ON u.id = b.teacher_id
    WHERE b.student_id = ? AND b.status = 'completed'
      AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.booking_id = b.id AND r.reviewer_id = ?)
    ORDER BY b.start_time DESC
");
$stmt->execute([$sid, $sid]);
$completedNoReview = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT r.*, u.name AS teacher_name, l.title AS listing_title
    FROM reviews r
    JOIN bookings b ON b.id = r.booking_id
    JOIN skill_listings l ON l.id = b.listing_id
    JOIN users u ON u.id = r.reviewee_id
    WHERE r.reviewer_id = ? ORDER BY r.created_at DESC
");
$stmt->execute([$sid]);
$myReviews = $stmt->fetchAll();

$pageTitle = 'My Reviews';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="section-title"><h1>My Reviews</h1></div>
    <?php include __DIR__ . '/../includes/alerts.php'; ?>

    <h2>Sessions Awaiting Review</h2>
    <?php if (empty($completedNoReview)): ?>
      <p class="help-text">No completed sessions awaiting review.</p>
    <?php else: ?>
      <div class="grid grid-2" style="margin-bottom:32px;">
        <?php foreach ($completedNoReview as $b): ?>
          <div class="card">
            <strong><?= e($b['listing_title']) ?></strong>
            <p class="help-text">Teacher: <?= e($b['teacher_name']) ?> &middot; <?= formatDate($b['start_time']) ?></p>
            <form method="POST" action="<?= BASE_URL ?>/student/review_action.php">
              <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
              <div class="form-group">
                <label>Rating</label>
                <select name="rating" required>
                  <option value="5">★★★★★ Excellent</option>
                  <option value="4">★★★★ Good</option>
                  <option value="3">★★★ Average</option>
                  <option value="2">★★ Poor</option>
                  <option value="1">★ Terrible</option>
                </select>
              </div>
              <div class="form-group">
                <label>Comment</label>
                <textarea name="comment" placeholder="Share your experience..."></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-sm">Submit Review</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h2>Reviews You've Written</h2>
    <?php if (empty($myReviews)): ?>
      <p class="help-text">You haven't written any reviews yet.</p>
    <?php else: ?>
      <div class="table-wrap card" style="padding:0;">
        <table>
          <thead><tr><th>Skill</th><th>Teacher</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($myReviews as $r): ?>
            <tr>
              <td><?= e($r['listing_title']) ?></td>
              <td><?= e($r['teacher_name']) ?></td>
              <td class="rating-stars">★ <?= (int)$r['rating'] ?></td>
              <td><?= e($r['comment'] ?? '-') ?></td>
              <td><?= formatDate($r['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
