<?php
// Database configuration for the Skill-Sharing Management System.
// Uses PDO with MySQL, per course requirement (PHP & MySQL).

$DB_HOST = '127.0.0.1';
$DB_NAME = 'skillshare';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed. Please check your .env / config/database.php settings. (' . $e->getMessage() . ')');
}