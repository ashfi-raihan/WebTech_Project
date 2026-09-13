<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/teacher/skills.php');

$action = $_POST['action'] ?? '';

if ($action === 'create' || $action === 'update') {
    $skillId = (int)($_POST['skill_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $level = $_POST['level'] ?? 'beginner';
    $price = (float)($_POST['price'] ?? 0);
    $duration = (int)($_POST['session_duration_minutes'] ?? 60);
    $allowsPaid = isset($_POST['allows_paid']) ? 1 : 0;
    $allowsBarter = isset($_POST['allows_barter']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';

    $errors = [];
    if (!$skillId) $errors[] = 'Please select a skill.';
    if (mb_strlen($title) < 3) $errors[] = 'Title must be at least 3 characters.';
    if ($price < 0) $errors[] = 'Price must be a valid non-negative number.';
    if ($duration < 15) $errors[] = 'Session duration must be at least 15 minutes.';
    if (!$allowsPaid && !$allowsBarter) $errors[] = 'Listing must allow at least paid or barter bookings.';

    if (!empty($errors)) {
        setFlashErrors($errors);
        $back = $action === 'update' ? '/teacher/skill_form.php?id=' . (int)$_POST['listing_id'] : '/teacher/skill_form.php';
        redirect($back);
    }

    if ($action === 'create') {
        $stmt = $pdo->prepare("
            INSERT INTO skill_listings (teacher_id, skill_id, title, description, level, price, session_duration_minutes, allows_paid, allows_barter)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tid, $skillId, $title, $description ?: null, $level, $price, $duration, $allowsPaid, $allowsBarter]);
    } else {
        $listingId = (int)($_POST['listing_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT id FROM skill_listings WHERE id = ? AND teacher_id = ?');
        $stmt->execute([$listingId, $tid]);
        if (!$stmt->fetch()) { http_response_code(404); include __DIR__ . '/../includes/error_404.php'; exit; }

        $stmt = $pdo->prepare("
            UPDATE skill_listings SET skill_id=?, title=?, description=?, level=?, price=?, session_duration_minutes=?,
              allows_paid=?, allows_barter=?, status=? WHERE id = ?
        ");
        $stmt->execute([$skillId, $title, $description ?: null, $level, $price, $duration, $allowsPaid, $allowsBarter, $status, $listingId]);
    }

    redirect('/teacher/skills.php');
}

if ($action === 'delete') {
    $listingId = (int)($_POST['listing_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM skill_listings WHERE id = ? AND teacher_id = ?');
    $stmt->execute([$listingId, $tid]);
    $listing = $stmt->fetch();
    if (!$listing) { http_response_code(404); include __DIR__ . '/../includes/error_404.php'; exit; }

    $activeBookings = (int)scalar($pdo, "SELECT COUNT(*) FROM bookings WHERE listing_id = ? AND status IN ('pending','confirmed','rescheduled')", [$listingId]);

    if ($activeBookings > 0) {
        $pdo->prepare("UPDATE skill_listings SET status='removed' WHERE id = ?")->execute([$listingId]);
    } else {
        $pdo->prepare('DELETE FROM skill_listings WHERE id = ?')->execute([$listingId]);
    }
    redirect('/teacher/skills.php');
}

redirect('/teacher/skills.php');
