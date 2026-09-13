<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
handleBarterRespond($pdo, '/teacher/barter.php');
