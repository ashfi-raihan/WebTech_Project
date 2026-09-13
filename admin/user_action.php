<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/users.php');

$userId = (int)($_POST['user_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) { http_response_code(404); include __DIR__ . '/../includes/error_404.php'; exit; }
if ($user['role'] === 'admin') {
    setFlashErrors(['Cannot deactivate an administrator account.']);
    redirect('/admin/users.php');
}

$pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$user['is_active'] ? 0 : 1, $userId]);
redirect('/admin/users.php');
