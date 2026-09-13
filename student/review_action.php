<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$sid = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/student/reviews.php');

$bookingId = (int)($_POST['booking_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) { setFlashErrors(['Booking not found.']); redirectBack('/student/reviews.php'); }
if ((int)$booking['student_id'] !== $sid) { http_response_code(403); include __DIR__ . '/../includes/error_403.php'; exit; }
if ($booking['status'] !== 'completed') {
    setFlashErrors(['You can only review sessions that have been completed.']);
    redirectBack('/student/reviews.php');
}

$stmt = $pdo->prepare('SELECT id FROM reviews WHERE booking_id = ? AND reviewer_id = ?');
$stmt->execute([$bookingId, $sid]);
if ($stmt->fetch()) {
    setFlashErrors(['You have already reviewed this session.']);
    redirectBack('/student/reviews.php');
}

if ($rating < 1 || $rating > 5) {
    setFlashErrors(['Please choose a rating between 1 and 5.']);
    redirectBack('/student/reviews.php');
}

$stmt = $pdo->prepare('INSERT INTO reviews (booking_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$bookingId, $sid, $booking['teacher_id'], $rating, $comment ?: null]);

notify($pdo, (int)$booking['teacher_id'], 'new_review', "You received a new $rating-star review.", '/teacher/reviews.php');

redirect('/student/reviews.php');
