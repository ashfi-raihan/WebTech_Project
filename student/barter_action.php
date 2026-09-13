<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$user = currentUser();
$uid = $user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/student/barter.php');

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $offeredSkillId = (int)($_POST['offered_skill_id'] ?? 0);
    $requestedSkillId = (int)($_POST['requested_skill_id'] ?? 0);
    $listingId = !empty($_POST['listing_id']) ? (int)$_POST['listing_id'] : null;
    $message = trim($_POST['message'] ?? '');

    if (!$receiverId || !$offeredSkillId || !$requestedSkillId) {
        setFlashErrors(['Please fill in all barter offer fields.']);
        redirectBack('/student/barter.php');
    }
    if ($receiverId === $uid) {
        setFlashErrors(['You cannot send a barter offer to yourself.']);
        redirectBack('/student/barter.php');
    }

    $stmt = $pdo->prepare("
        INSERT INTO barter_exchanges (sender_id, receiver_id, offered_skill_id, requested_skill_id, listing_id, message, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$uid, $receiverId, $offeredSkillId, $requestedSkillId, $listingId, $message ?: null]);

    notify($pdo, $receiverId, 'barter_request', "{$user['name']} sent you a barter exchange offer.", '/teacher/barter.php');
    redirect('/student/barter.php');
}

if ($action === 'respond') {
    handleBarterRespond($pdo, '/student/barter.php');
}

redirect('/student/barter.php');
