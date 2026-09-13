<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$adminId = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/reports.php');

$reportId = (int)($_POST['report_id'] ?? 0);
$modAction = $_POST['mod_action'] ?? 'reviewed_no_action';
$notes = trim($_POST['notes'] ?? '');
$status = $_POST['status'] ?? 'resolved';

$stmt = $pdo->prepare('SELECT * FROM reports WHERE id = ?');
$stmt->execute([$reportId]);
$report = $stmt->fetch();
if (!$report) { http_response_code(404); include __DIR__ . '/../includes/error_404.php'; exit; }

$pdo->beginTransaction();
$pdo->prepare('UPDATE reports SET status = ? WHERE id = ?')->execute([$status, $reportId]);
$pdo->prepare('INSERT INTO moderation_records (report_id, admin_id, action, notes) VALUES (?, ?, ?, ?)')
    ->execute([$reportId, $adminId, $modAction, $notes ?: null]);

if ($modAction === 'listing_removed' && $report['target_type'] === 'listing') {
    $pdo->prepare("UPDATE skill_listings SET status='removed' WHERE id = ?")->execute([$report['target_id']]);
}
if ($modAction === 'user_deactivated' && $report['target_type'] === 'user') {
    $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$report['target_id']]);
}
$pdo->commit();

redirect('/admin/reports.php');
