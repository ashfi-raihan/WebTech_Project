<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');
handleProfileAction($pdo, 'student');
