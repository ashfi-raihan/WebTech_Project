<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$user = currentUser();
$sid = $user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/student/bookings.php');

$action = $_POST['action'] ?? '';

// ---------- CREATE ----------
if ($action === 'create') {
    $availabilityId = (int)($_POST['availability_id'] ?? 0);
    $paymentType = $_POST['payment_type'] ?? 'paid';

    $stmt = $pdo->prepare("
        SELECT a.*, l.price, l.teacher_id, l.title, l.allows_paid, l.allows_barter, l.id AS listing_id
        FROM teacher_availability a
        JOIN skill_listings l ON l.id = a.listing_id
        WHERE a.id = ?
    ");
    $stmt->execute([$availabilityId]);
    $slot = $stmt->fetch();

    if (!$slot) { setFlashErrors(['Selected time slot does not exist.']); redirectBack('/listings.php'); }
    if ($slot['is_booked']) { setFlashErrors(['Sorry, this slot has already been booked. Please choose another.']); redirect('/listing.php?id=' . $slot['listing_id']); }
    if (strtotime($slot['start_time']) <= time()) { setFlashErrors(['Cannot book a slot in the past.']); redirect('/listing.php?id=' . $slot['listing_id']); }
    if ($paymentType === 'barter' && !$slot['allows_barter']) { setFlashErrors(['This listing does not accept barter bookings.']); redirect('/listing.php?id=' . $slot['listing_id']); }
    if ($paymentType === 'paid' && !$slot['allows_paid']) { setFlashErrors(['This listing does not accept paid bookings.']); redirect('/listing.php?id=' . $slot['listing_id']); }

    try {
        $pdo->beginTransaction();

        // Atomic conditional update prevents double-booking under concurrent requests.
        $stmt = $pdo->prepare("UPDATE teacher_availability SET is_booked = 1 WHERE id = ? AND is_booked = 0");
        $stmt->execute([$availabilityId]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('SLOT_TAKEN');
        }

        $status = $paymentType === 'barter' ? 'pending' : 'confirmed';
        $meetingId = generateMeetingId();

        $stmt = $pdo->prepare("
            INSERT INTO bookings (student_id, teacher_id, listing_id, availability_id, start_time, end_time, payment_type, status, meeting_id, meeting_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$sid, $slot['teacher_id'], $slot['listing_id'], $slot['id'], $slot['start_time'], $slot['end_time'], $paymentType, $status, $meetingId, null]);
        $bookingId = (int)$pdo->lastInsertId();

        $pdo->prepare("UPDATE bookings SET meeting_url = ? WHERE id = ?")->execute(["/shared/meeting.php?id=$bookingId", $bookingId]);

        if ($paymentType === 'paid') {
            $payment = processDemoPayment((float)$slot['price']);
            if (!$payment['success']) throw new RuntimeException('PAYMENT_FAILED');
            $stmt = $pdo->prepare("
                INSERT INTO transactions (booking_id, payer_id, payee_id, amount, type, status, reference)
                VALUES (?, ?, ?, ?, 'session_payment', 'success', ?)
            ");
            $stmt->execute([$bookingId, $sid, $slot['teacher_id'], $slot['price'], $payment['reference']]);
        }

        notify($pdo, (int)$slot['teacher_id'], 'booking', "New booking request for \"{$slot['title']}\".", '/teacher/bookings.php');
        notify($pdo, $sid, 'booking_confirmed', "Your booking for \"{$slot['title']}\" is $status.", '/student/bookings.php');

        $pdo->commit();
    } catch (RuntimeException $e) {
        $pdo->rollBack();
        $msg = $e->getMessage() === 'SLOT_TAKEN'
            ? 'Sorry, this slot was just booked by someone else.'
            : ($e->getMessage() === 'PAYMENT_FAILED' ? 'Payment could not be processed. Please try again.' : 'Something went wrong while creating the booking.');
        setFlashErrors([$msg]);
        redirect('/listing.php?id=' . $slot['listing_id']);
    }

    redirect('/student/bookings.php');
}

// ---------- CANCEL ----------
if ($action === 'cancel') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking || $booking['student_id'] != $sid) { http_response_code(403); include __DIR__ . '/../includes/error_403.php'; exit; }
    if (in_array($booking['status'], ['completed', 'cancelled'], true)) {
        setFlashErrors(['This booking cannot be cancelled.']);
        redirectBack('/student/bookings.php');
    }

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$bookingId]);
    if ($booking['availability_id']) {
        $pdo->prepare("UPDATE teacher_availability SET is_booked = 0 WHERE id = ?")->execute([$booking['availability_id']]);
    }
    notify($pdo, (int)$booking['teacher_id'], 'booking_cancelled', "A booking (#$bookingId) has been cancelled.", '/teacher/bookings.php');
    $pdo->commit();

    redirectBack('/student/bookings.php');
}

// ---------- RESCHEDULE ----------
if ($action === 'reschedule') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $newAvailabilityId = (int)($_POST['new_availability_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking || $booking['student_id'] != $sid) { http_response_code(403); include __DIR__ . '/../includes/error_403.php'; exit; }
    if (!in_array($booking['status'], ['pending', 'confirmed'], true)) {
        setFlashErrors(['This booking cannot be rescheduled.']);
        redirectBack('/student/bookings.php');
    }

    $stmt = $pdo->prepare('SELECT * FROM teacher_availability WHERE id = ? AND listing_id = ?');
    $stmt->execute([$newAvailabilityId, $booking['listing_id']]);
    $newSlot = $stmt->fetch();

    if (!$newSlot || $newSlot['is_booked']) {
        setFlashErrors(['Selected new slot is not available.']);
        redirectBack('/student/bookings.php');
    }

    $pdo->beginTransaction();
    if ($booking['availability_id']) {
        $pdo->prepare('UPDATE teacher_availability SET is_booked = 0 WHERE id = ?')->execute([$booking['availability_id']]);
    }
    $pdo->prepare('UPDATE teacher_availability SET is_booked = 1 WHERE id = ?')->execute([$newSlot['id']]);
    $pdo->prepare("UPDATE bookings SET availability_id = ?, start_time = ?, end_time = ?, status = 'rescheduled' WHERE id = ?")
        ->execute([$newSlot['id'], $newSlot['start_time'], $newSlot['end_time'], $bookingId]);
    notify($pdo, (int)$booking['teacher_id'], 'booking_rescheduled', "Booking #$bookingId was rescheduled by the student.", '/teacher/bookings.php');
    $pdo->commit();

    redirectBack('/student/bookings.php');
}

redirect('/student/bookings.php');
