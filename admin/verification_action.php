<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$adminId = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/verification.php');

$requestId = (int)($_POST['request_id'] ?? 0);
$reviewAction = $_POST['review_action'] ?? '';
$adminNotes = trim($_POST['admin_notes'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM verification_requests WHERE id = ?');
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) { http_response_code(404); include __DIR__ . '/../includes/error_404.php'; exit; }
if ($request['status'] !== 'pending') {
    setFlashErrors(['This request has already been reviewed.']);
    redirect('/admin/verification.php');
}

$newStatus = $reviewAction === 'approve' ? 'approved' : 'rejected';

$pdo->beginTransaction();
$pdo->prepare("UPDATE verification_requests SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
    ->execute([$newStatus, $adminNotes ?: null, $adminId, $requestId]);
$pdo->prepare('UPDATE users SET verification_status = ? WHERE id = ?')->execute([$newStatus, $request['user_id']]);
notify($pdo, (int)$request['user_id'], 'verification_result', "Your verification request was $newStatus.", '/index.php');
$pdo->commit();

redirect('/admin/verification.php');
