<?php
require_once __DIR__ . '/config/config.php';

$q = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$level = $_GET['level'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$min_rating = $_GET['min_rating'] ?? '';
$payment_type = $_GET['payment_type'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$sql = "
    SELECT l.*, s.name AS skill_name, c.name AS category_name,
           u.id AS teacher_id, u.name AS teacher_name, u.verification_status AS teacher_verification,
           COALESCE(AVG(r.rating), 0) AS avg_rating,
           COUNT(DISTINCT r.id) AS review_count
    FROM skill_listings l
    JOIN skills s ON s.id = l.skill_id
    LEFT JOIN categories c ON c.id = s.category_id
    JOIN users u ON u.id = l.teacher_id
    LEFT JOIN bookings b ON b.listing_id = l.id
    LEFT JOIN reviews r ON r.booking_id = b.id AND r.reviewee_id = l.teacher_id
    WHERE l.status = 'active'
";
$params = [];

if ($q !== '') {
    $sql .= " AND (l.title LIKE ? OR l.description LIKE ? OR s.name LIKE ? OR u.name LIKE ?)";
    $like = "%$q%";
    array_push($params, $like, $like, $like, $like);
}
if ($category !== '') { $sql .= " AND c.id = ?"; $params[] = $category; }
if ($level !== '') { $sql .= " AND l.level = ?"; $params[] = $level; }
if ($price_max !== '') { $sql .= " AND l.price <= ?"; $params[] = $price_max; }
if ($payment_type === 'paid') { $sql .= " AND l.allows_paid = 1"; }
elseif ($payment_type === 'barter') { $sql .= " AND l.allows_barter = 1"; }

$sql .= " GROUP BY l.id";
if ($min_rating !== '') { $sql .= " HAVING avg_rating >= ?"; $params[] = $min_rating; }

$sql .= match ($sort) {
    'price_asc' => " ORDER BY l.price ASC",
    'price_desc' => " ORDER BY l.price DESC",
    'rating' => " ORDER BY avg_rating DESC",
    default => " ORDER BY l.created_at DESC",
};

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$pageTitle = 'Browse Skills';
include __DIR__ . '/includes/header.php';
?>
<div class="container" style="padding:32px 20px;">
  <div class="section-title"><h1>Browse Skills</h1></div>

  <form method="GET" action="<?= BASE_URL ?>/listings.php" class="card" style="margin-bottom:24px;">
    <div class="form-row">
      <div class="form-group">
        <label>Search</label>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Skill, title, or teacher name">
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="category">
          <option value="">All Categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (string)$category === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Level</label>
        <select name="level">
          <option value="">Any Level</option>
          <?php foreach (['beginner','intermediate','advanced','expert'] as $l): ?>
            <option value="<?= $l ?>" <?= $level === $l ? 'selected' : '' ?>><?= ucfirst($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Max Price (৳)</label>
        <input type="number" name="price_max" value="<?= e($price_max) ?>" placeholder="e.g. 1000">
      </div>
      <div class="form-group">
        <label>Min Rating</label>
        <select name="min_rating">
          <option value="">Any Rating</option>
          <?php foreach ([4,3,2,1] as $r): ?>
            <option value="<?= $r ?>" <?= (string)$min_rating === (string)$r ? 'selected' : '' ?>><?= $r ?>+ stars</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Payment Type</label>
        <select name="payment_type">
          <option value="">Paid or Barter</option>
          <option value="paid" <?= $payment_type === 'paid' ? 'selected' : '' ?>>Paid Only</option>
          <option value="barter" <?= $payment_type === 'barter' ? 'selected' : '' ?>>Barter Only</option>
        </select>
      </div>
      <div class="form-group">
        <label>Sort By</label>
        <select name="sort">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
          <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary">Apply Filters</button>
    <a href="<?= BASE_URL ?>/listings.php" class="btn btn-outline">Reset</a>
  </form>

  <?php if (empty($listings)): ?>
    <div class="empty-state"><div class="icon">🔎</div><p>No skill listings match your filters. Try adjusting your search.</p></div>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($listings as $l): ?>
        <a href="<?= BASE_URL ?>/listing.php?id=<?= (int)$l['id'] ?>" class="card card-hover listing-card">
          <h3><?= e($l['title']) ?></h3>
          <div class="meta">
            <span class="badge badge-info"><?= e($l['skill_name']) ?></span>
            <span class="badge badge-muted"><?= e($l['level']) ?></span>
            <?php if ($l['teacher_verification'] === 'approved'): ?><span class="badge badge-success">✓ Verified</span><?php endif; ?>
          </div>
          <p style="color:var(--text-muted);font-size:0.9rem;">by <?= e($l['teacher_name']) ?> &middot; <?= (int)$l['session_duration_minutes'] ?> min</p>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-top:auto;">
            <span class="price-tag"><?= $l['price'] > 0 ? '৳' . $l['price'] : '' ?><?= $l['allows_barter'] ? ($l['price'] > 0 ? ' / Barter' : 'Barter only') : '' ?></span>
            <span class="rating-stars">★ <?= number_format((float)$l['avg_rating'], 1) ?> (<?= (int)$l['review_count'] ?>)</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
