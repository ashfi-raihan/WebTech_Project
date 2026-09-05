<?php
require 'db_connect.php';
header('Content-Type: application/json');

$stmt = $pdo->prepare("SELECT skill_id, title, description, price, mode FROM skills ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$skills = $stmt->fetchAll();

echo json_encode(["status" => "success", "data" => $skills]);
?>