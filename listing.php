<?php
require_once __DIR__ . '/config/config.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT l.*, s.name AS skill_name, c.name AS category_name,
           u.id AS teacher_id, u.name AS teacher_name, u.verification_status AS teacher_verification
    FROM skill_listings l
    JOIN skills s ON s.id = l.skill_id
    LEFT JOIN categories c ON c.id = s.category_id
    JOIN users u ON u.id = l.teacher_id
    WHERE l.id = ?
");
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) {
    http_response_code(404);
    include __DIR__ . '/includes/error_404.php';
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM teacher_availability
    WHERE listing_id = ? AND is_booked = 0 AND start_time > NOW()
    ORDER BY start_time ASC
");
$stmt->execute([$id]);
$availability = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT r.*, u.name AS reviewer_name
    FROM reviews r
    JOIN bookings b ON b.id = r.booking_id
    JOIN users u ON u.id = r.reviewer_id
    WHERE b.listing_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT COALESCE(AVG(r.rating),0) AS avg_rating, COUNT(r.id) AS review_count
    FROM reviews r JOIN bookings b ON b.id = r.booking_id WHERE b.listing_id = ?
");
$stmt->execute([$id]);
$ratingRow = $stmt->fetch();

$user = currentUser();
$inWishlist = false;
if ($user && $user['role'] === 'student') {
    $stmt = $pdo->prepare('SELECT 1 FROM wishlists WHERE student_id = ? AND listing_id = ?');
    $stmt->execute([$user['id'], $id]);
    $inWishlist = (bool)$stmt->fetchColumn();
}

$pageTitle = $listing['title'];
include __DIR__ . '/includes/header.php';
?>
<div class="container" style="padding:32px 20px;max-width:920px;">
  <?php include __DIR__ . '/includes/alerts.php'; ?>

  <div class="card" style="margin-bottom:24px;">
    <div class="section-title">
      <div>
        <h1 style="margin-bottom:4px;"><?= e($listing['title']) ?></h1>
        <p style="color:var(--text-muted);">
          by <?= e($listing['teacher_name']) ?>
          <?php if ($listing['teacher_verification'] === 'approved'): ?> <span class="badge badge-success">✓ Verified</span><?php endif; ?>
        </p>
      </div>
      <?php if ($user && $user['role'] === 'student'): ?>
        <?php if ($inWishlist): ?>
          <form method="POST" action="<?= BASE_URL ?>/student/wishlist_action.php">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="listing_id" value="<?= (int)$listing['id'] ?>">
            <button type="submit" class="btn btn-outline btn-sm">♥ Remove from Wishlist</button>
          </form>
        <?php else: ?>
          <form method="POST" action="<?= BASE_URL ?>/student/wishlist_action.php">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="listing_id" value="<?= (int)$listing['id'] ?>">
            <button type="submit" class="btn btn-outline btn-sm">♡ Add to Wishlist</button>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="meta" style="margin-bottom:16px;">
      <span class="badge badge-info"><?= e($listing['skill_name']) ?></span>
      <span class="badge badge-muted"><?= e($listing['category_name']) ?></span>
      <span class="badge badge-muted"><?= e($listing['level']) ?></span>
      <span class="badge badge-muted"><?= (int)$listing['session_duration_minutes'] ?> min/session</span>
      <?php if ($listing['allows_paid']): ?><span class="badge badge-success">Paid</span><?php endif; ?>
      <?php if ($listing['allows_barter']): ?><span class="badge badge-warning">Barter Accepted</span><?php endif; ?>
    </div>

    <p><?= nl2br(e($listing['description'])) ?></p>

    <div style="display:flex;align-items:center;gap:16px;">
      <span class="price-tag" style="font-size:1.4rem;"><?= $listing['price'] > 0 ? '৳' . $listing['price'] : 'Free / Barter' ?></span>
      <span class="rating-stars">★ <?= number_format((float)$ratingRow['avg_rating'], 1) ?> (<?= (int)$ratingRow['review_count'] ?> reviews)</span>
    </div>
  </div>

  <div class="card" style="margin-bottom:24px;">
    <h2>Available Time Slots</h2>
    <?php if (empty($availability)): ?>
      <div class="empty-state"><p>No open time slots right now. Check back later.</p></div>
    <?php elseif (!$user): ?>
      <p>Please <a href="<?= BASE_URL ?>/auth/login.php">login</a> as a student to book a session.</p>
      <div class="grid grid-3">
        <?php foreach ($availability as $a): ?><div class="card"><?= formatDateTime($a['start_time']) ?></div><?php endforeach; ?>
      </div>
    <?php elseif ($user['role'] !== 'student'): ?>
      <p class="help-text">Only students can book sessions.</p>
    <?php else: ?>
      <div class="grid grid-3">
        <?php foreach ($availability as $a): ?>
          <form method="POST" action="<?= BASE_URL ?>/student/booking_action.php" class="card">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="availability_id" value="<?= (int)$a['id'] ?>">
            <p><strong><?= formatDateTime($a['start_time']) ?></strong></p>
            <p class="help-text">to <?= date('g:i A', strtotime($a['end_time'])) ?></p>
            <div class="form-group">
              <label>Payment Type</label>
              <select name="payment_type">
                <?php if ($listing['allows_paid']): ?><option value="paid">Pay ৳<?= $listing['price'] ?> (Demo Payment)</option><?php endif; ?>
                <?php if ($listing['allows_barter']): ?><option value="barter">Request Barter</option><?php endif; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-sm">Book This Slot</button>
          </form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Reviews (<?= (int)$ratingRow['review_count'] ?>)</h2>
    <?php if (empty($reviews)): ?>
      <p class="help-text">No reviews yet.</p>
    <?php else: ?>
      <?php foreach ($reviews as $r): ?>
        <div style="border-bottom:1px solid var(--border);padding:12px 0;">
          <strong><?= e($r['reviewer_name']) ?></strong> &middot; <span class="rating-stars">★ <?= (int)$r['rating'] ?></span>
          <p style="margin:4px 0 0;"><?= e($r['comment']) ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
