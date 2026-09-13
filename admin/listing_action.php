<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/listings.php');

$listingId = (int)($_POST['listing_id'] ?? 0);
$pdo->prepare("UPDATE skill_listings SET status='removed' WHERE id = ?")->execute([$listingId]);
redirect('/admin/listings.php');
