<?php
require 'db_connect.php';
header('Content-Type: application/json');

$skill_id = 1; // Hardcoded for testing; usually passed via URL or hidden input
$learner_id = 1; // Hardcoded for testing
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$notes = $_POST['notes'] ?? '';

$stmt = $pdo->prepare("INSERT INTO bookings (skill_id, learner_id, booking_date, booking_time, notes) VALUES (?, ?, ?, ?, ?)");
if ($stmt->execute([$skill_id, $learner_id, $date, $time, $notes])) {
    echo json_encode(["status" => "success", "message" => "Session booked successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Booking failed."]);
}
?>