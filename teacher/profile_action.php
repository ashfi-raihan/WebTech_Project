<?php
require_once __DIR__ . '/../config/config.php';
requireRole('teacher');
handleProfileAction($pdo, 'teacher');
