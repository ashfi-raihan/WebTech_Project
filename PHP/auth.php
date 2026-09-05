<?php
require 'db_connect.php';
header('Content-Type: application/json');
session_start();

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $role = $_POST['role'] ?? 'learner';

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    try {
        $stmt->execute([$name, $email, $password, $role]);
        echo json_encode(["status" => "success", "message" => "Registration successful!"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Email already exists."]);
    }
} elseif ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        echo json_encode(["status" => "success", "message" => "Login successful!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
    }
}
?>