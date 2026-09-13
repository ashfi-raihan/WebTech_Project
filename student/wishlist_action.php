<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
$sid = currentUser()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/student/wishlist.php');

$action = $_POST['action'] ?? '';
$listingId = (int)($_POST['listing_id'] ?? 0);

if ($action === 'add') {
    try {
        $stmt = $pdo->prepare('INSERT INTO wishlists (student_id, listing_id) VALUES (?, ?)');
        $stmt->execute([$sid, $listingId]);
    } catch (PDOException $e) {
        // Duplicate (already in wishlist) - ignore silently, same as original behavior.
    }
} elseif ($action === 'remove') {
    $stmt = $pdo->prepare('DELETE FROM wishlists WHERE student_id = ? AND listing_id = ?');
    $stmt->execute([$sid, $listingId]);
}

redirectBack('/student/wishlist.php');
