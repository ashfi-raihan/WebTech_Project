<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();
$user = currentUser();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT b.*, l.title AS listing_title, s.name AS student_name, t.name AS teacher_name
    FROM bookings b
    JOIN skill_listings l ON l.id = b.listing_id
    JOIN users s ON s.id = b.student_id
    JOIN users t ON t.id = b.teacher_id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) { http_response_code(404); include __DIR__ . '/../includes/error_404.php'; exit; }
if ((int)$booking['student_id'] !== $user['id'] && (int)$booking['teacher_id'] !== $user['id']) {
    http_response_code(403); include __DIR__ . '/../includes/error_403.php'; exit;
}
if (!in_array($booking['status'], ['confirmed', 'rescheduled', 'completed'], true)) {
    http_response_code(403);
    $message = 'This session is not confirmed yet.';
    include __DIR__ . '/../includes/error_403.php';
    exit;
}

$pageTitle = 'Meeting Room';
include __DIR__ . '/../includes/header.php';
?>
<div class="container-sm" style="padding:40px 20px;">
  <div class="meeting-screen">
    <h1 style="color:#fff;">🎥 <?= e($booking['listing_title']) ?></h1>
    <p style="opacity:0.85;">Session between <?= e($booking['student_name']) ?> and <?= e($booking['teacher_name']) ?></p>
    <p style="opacity:0.7;font-size:0.9rem;">Meeting ID: <?= e($booking['meeting_id']) ?></p>
    <div style="margin:32px 0;">
      <span class="badge <?= $booking['status'] === 'completed' ? 'badge-muted' : 'badge-success' ?>" style="font-size:0.95rem;">
        <?= $booking['status'] === 'completed' ? 'Session Ended' : 'Room Ready' ?>
      </span>
    </div>
    <?php if ($booking['status'] !== 'completed'): ?>
      <button class="btn btn-primary" onclick="alert('This is a demo meeting room. In production this would launch a real video call via a provider like Zoom, Jitsi, or Google Meet.')">Join Session</button>
    <?php endif; ?>
  </div>
  <div class="card" style="margin-top:24px;">
    <h3>About This Meeting Room</h3>
    <p class="help-text">
      This is a demo meeting-room architecture built for coursework purposes. It shows the booking's meeting ID,
      join mechanism, and status, backed by the database. To use a real video provider, swap the "Join Session"
      action for that provider's SDK/API using this same <code>meeting_id</code> / <code>meeting_url</code> pattern.
    </p>
  </div>
  <a href="javascript:history.back()" class="btn btn-outline" style="margin-top:16px;">&larr; Back</a>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
