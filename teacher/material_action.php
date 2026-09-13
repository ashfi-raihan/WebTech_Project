<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
$tid = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/teacher/materials.php');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $fileUrl = trim($_POST['file_url'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND teacher_id = ?');
    $stmt->execute([$bookingId, $tid]);
    if (!$stmt->fetch()) {
        setFlashErrors(['Invalid session selected.']);
        redirect('/teacher/materials.php');
    }
    if (mb_strlen($title) < 2) {
        setFlashErrors(['Material title is required.']);
        redirect('/teacher/materials.php');
    }

    $stmt = $pdo->prepare('INSERT INTO learning_materials (booking_id, teacher_id, title, description, file_url) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$bookingId, $tid, $title, $description ?: null, $fileUrl ?: null]);

    redirect('/teacher/materials.php');
}

if ($action === 'delete') {
    $materialId = (int)($_POST['material_id'] ?? 0);
    $pdo->prepare('DELETE FROM learning_materials WHERE id = ? AND teacher_id = ?')->execute([$materialId, $tid]);
    redirect('/teacher/materials.php');
}

redirect('/teacher/materials.php');
