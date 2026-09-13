<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin/skills.php');

$action = $_POST['action'] ?? '';

if ($action === 'create_category') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if (mb_strlen($name) < 2) {
        setFlashErrors(['Category name is required.']);
        redirect('/admin/skills.php');
    }
    try {
        $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)')->execute([$name, $description ?: null]);
    } catch (PDOException $e) {
        setFlashErrors(['A category with this name already exists.']);
    }
    redirect('/admin/skills.php');
}

if ($action === 'delete_category') {
    $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([(int)($_POST['category_id'] ?? 0)]);
    redirect('/admin/skills.php');
}

if ($action === 'create_skill') {
    $name = trim($_POST['name'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    if (mb_strlen($name) < 2) {
        setFlashErrors(['Skill name is required.']);
        redirect('/admin/skills.php');
    }
    try {
        $pdo->prepare('INSERT INTO skills (name, category_id) VALUES (?, ?)')->execute([$name, $categoryId]);
    } catch (PDOException $e) {
        setFlashErrors(['A skill with this name already exists.']);
    }
    redirect('/admin/skills.php');
}

if ($action === 'delete_skill') {
    $pdo->prepare('DELETE FROM skills WHERE id = ?')->execute([(int)($_POST['skill_id'] ?? 0)]);
    redirect('/admin/skills.php');
}

redirect('/admin/skills.php');
