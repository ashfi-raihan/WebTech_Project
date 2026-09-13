<?php
require_once __DIR__ . '/../config/config.php';
requireRole('employer');
handleProfileAction($pdo, 'employer');
