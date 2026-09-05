<?php
require 'db_connect.php';
header('Content-Type: application/json');

$booking_id = 1; // Hardcoded for testing
$rating = $_POST['rating'] ?? 5;
$feedback = $_POST['feedback'] ?? '';

$stmt = $pdo->prepare("INSERT INTO reviews (booking_id, rating, feedback) VALUES (?, ?, ?)");
if ($stmt->execute([$booking_id, $rating, $feedback])) {
    echo json_encode(["status" => "success", "message" => "Review submitted. Thank you!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to submit review."]);
}
?>