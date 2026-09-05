<?php
require 'db_connect.php';
header('Content-Type: application/json');

$learner_id = 1; // Hardcoded for testing. In production, use $_SESSION['user_id']

try {
    $stmt = $pdo->prepare("SELECT b.booking_date, b.booking_time, b.status, s.title 
                           FROM bookings b 
                           JOIN skills s ON b.skill_id = s.skill_id 
                           WHERE b.learner_id = ? 
                           ORDER BY b.booking_date ASC");
    $stmt->execute([$learner_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $sessions]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Failed to fetch sessions."]);
}
?>