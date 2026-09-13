<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/teacher/bookings.php');

$action = $_POST['action'] ?? '';
$bookingId = (int)($_POST['booking_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking || (int)$booking['teacher_id'] !== $tid) { http_response_code(403); include __DIR__ . '/../includes/error_403.php'; exit; }

if ($action === 'confirm') {
    if ($booking['status'] !== 'pending') {
        setFlashErrors(['Only pending bookings can be confirmed.']);
        redirectBack('/teacher/bookings.php');
    }
    $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$bookingId]);
    notify($pdo, (int)$booking['student_id'], 'booking_confirmed', "Your booking #$bookingId was confirmed by the teacher.", '/student/bookings.php');
    redirectBack('/teacher/bookings.php');
}

if ($action === 'complete') {
    if (!in_array($booking['status'], ['confirmed', 'rescheduled'], true)) {
        setFlashErrors(['Only confirmed sessions can be marked as completed.']);
        redirectBack('/teacher/bookings.php');
    }
    $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?")->execute([$bookingId]);
    notify($pdo, (int)$booking['student_id'], 'session_completed', "Your session #$bookingId was marked as completed. You can now leave a review!", '/student/reviews.php');
    redirectBack('/teacher/bookings.php');
}

if ($action === 'cancel') {
    if (in_array($booking['status'], ['completed', 'cancelled'], true)) {
        setFlashErrors(['This booking cannot be cancelled.']);
        redirectBack('/teacher/bookings.php');
    }
    $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$bookingId]);
    if ($booking['availability_id']) {
        $pdo->prepare('UPDATE teacher_availability SET is_booked = 0 WHERE id = ?')->execute([$booking['availability_id']]);
    }
    notify($pdo, (int)$booking['student_id'], 'booking_cancelled', "A booking (#$bookingId) has been cancelled.", '/student/bookings.php');
    redirectBack('/teacher/bookings.php');
}

redirect('/teacher/bookings.php');
