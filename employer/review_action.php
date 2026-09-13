<?php
require_once __DIR__ . '/../config/config.php';
requireRole('employer');
$eid = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/employer/hiring.php');

$hiringId = (int)($_POST['hiring_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM hiring_records WHERE id = ?');
$stmt->execute([$hiringId]);
$hiring = $stmt->fetch();

if (!$hiring) { setFlashErrors(['Hiring record not found.']); redirectBack('/employer/hiring.php'); }
if ((int)$hiring['employer_id'] !== $eid) { http_response_code(403); include __DIR__ . '/../includes/error_403.php'; exit; }
if (!in_array($hiring['status'], ['hired', 'completed'], true)) {
    setFlashErrors(['You can only review candidates you have hired.']);
    redirectBack('/employer/hiring.php');
}

$stmt = $pdo->prepare('SELECT id FROM reviews WHERE hiring_id = ? AND reviewer_id = ?');
$stmt->execute([$hiringId, $eid]);
if ($stmt->fetch()) {
    setFlashErrors(['You have already reviewed this engagement.']);
    redirectBack('/employer/hiring.php');
}

if ($rating < 1 || $rating > 5) {
    setFlashErrors(['Please choose a rating between 1 and 5.']);
    redirectBack('/employer/hiring.php');
}

$stmt = $pdo->prepare('INSERT INTO reviews (hiring_id, reviewer_id, reviewee_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$hiringId, $eid, $hiring['candidate_id'], $rating, $comment ?: null]);

notify($pdo, (int)$hiring['candidate_id'], 'new_review', "You received a new $rating-star review from an employer.", '/teacher/reviews.php');

redirect('/employer/hiring.php');
