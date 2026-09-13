<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
handleProfileAction($pdo, 'admin');
