<?php
require 'db_connect.php';
header('Content-Type: application/json');

try {
    // Fetch skills along with the mentor's name
    $stmt = $pdo->query("SELECT s.skill_id, s.title, s.category, s.price, s.mode, u.full_name as mentor_name 
                         FROM skills s 
                         JOIN users u ON s.user_id = u.id 
                         ORDER BY s.created_at DESC");
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $skills]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Failed to fetch skills."]);
}
?>