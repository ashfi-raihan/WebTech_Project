<?php
// Core application configuration: loads .env (no external libraries needed),
// starts the session, and pulls in the database connection.

// ---------- Minimal .env loader (no Composer/vendor packages required) ----------
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- Constants ----------
define('APP_NAME', 'SkillShare');
define('BASE_URL', rtrim(getenv('BASE_URL') ?: '', '/'));

// ---------- Database connection (provides $pdo) ----------
require_once __DIR__ . '/database.php';

// ---------- Shared helper functions ----------
require_once __DIR__ . '/../includes/functions.php';
