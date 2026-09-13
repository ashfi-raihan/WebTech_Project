<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();
$uid = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/index.php');

$targetType = $_POST['target_type'] ?? '';
$targetId = (int)($_POST['target_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if (!in_array($targetType, ['user', 'listing', 'review'], true)) {
    setFlashErrors(['Invalid report target type.']);
    redirectBack('/index.php');
}
if (mb_strlen($reason) < 5) {
    setFlashErrors(['Please provide a reason with at least 5 characters.']);
    redirectBack('/index.php');
}

$stmt = $pdo->prepare("INSERT INTO reports (reporter_id, target_type, target_id, reason, status) VALUES (?, ?, ?, ?, 'open')");
$stmt->execute([$uid, $targetType, $targetId, $reason]);

setFlashSuccess('Your report has been submitted to the administrators.');
redirectBack('/index.php');
