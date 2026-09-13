<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/teacher/availability.php');

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $listingId = (int)($_POST['listing_id'] ?? 0);
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM skill_listings WHERE id = ? AND teacher_id = ?');
    $stmt->execute([$listingId, $tid]);
    if (!$stmt->fetch()) {
        setFlashErrors(['Invalid skill listing selected.']);
        redirect('/teacher/availability.php');
    }

    $start = strtotime($startTime);
    $end = strtotime($endTime);
    $errors = [];
    if (!$start || !$end) $errors[] = 'Please provide valid start and end times.';
    elseif ($end <= $start) $errors[] = 'End time must be after start time.';
    elseif ($start <= time()) $errors[] = 'Start time must be in the future.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM teacher_availability
            WHERE teacher_id = ? AND NOT (end_time <= ? OR start_time >= ?)
        ");
        $stmt->execute([$tid, $startTime, $endTime]);
        if ((int)$stmt->fetchColumn() > 0) $errors[] = 'This time slot overlaps with an existing availability slot.';
    }

    if (!empty($errors)) {
        setFlashErrors($errors);
        redirect('/teacher/availability.php');
    }

    $stmt = $pdo->prepare("INSERT INTO teacher_availability (teacher_id, listing_id, start_time, end_time, is_booked) VALUES (?, ?, ?, ?, 0)");
    $stmt->execute([$tid, $listingId, $startTime, $endTime]);

    redirect('/teacher/availability.php');
}

if ($action === 'delete') {
    $slotId = (int)($_POST['slot_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM teacher_availability WHERE id = ? AND teacher_id = ?');
    $stmt->execute([$slotId, $tid]);
    $slot = $stmt->fetch();
    if (!$slot) { http_response_code(404); include __DIR__ . '/../includes/error_404.php'; exit; }

    if ($slot['is_booked']) {
        setFlashErrors(['Cannot remove a slot that is already booked.']);
        redirect('/teacher/availability.php');
    }

    $pdo->prepare('DELETE FROM teacher_availability WHERE id = ?')->execute([$slotId]);
    redirect('/teacher/availability.php');
}

redirect('/teacher/availability.php');
