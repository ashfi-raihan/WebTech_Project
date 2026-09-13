<?php
require_once __DIR__ . '/../config/config.php';
requireRole('employer');
$data = getProfileData($pdo, currentUser()['id']);
extract($data);
$allSkills = $pdo->query('SELECT * FROM skills ORDER BY name')->fetchAll();

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <h1>My Profile</h1>
    <?php include __DIR__ . '/../includes/profile_content.php'; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
