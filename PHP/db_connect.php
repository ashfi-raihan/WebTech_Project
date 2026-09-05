<?php
$host = 'localhost';
$dbname = 'skillbridge_db';
$username = 'root'; // Default XAMPP/WAMP username
$password = ''; // Default XAMPP/WAMP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["status" => "error", "message" => "Database connection failed."]));
}
?>