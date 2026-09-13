<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    redirect('/' . currentUser()['role'] . '/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $errors[] = 'Invalid email or password.';
    } elseif (!$user['is_active']) {
        $errors[] = 'Your account has been deactivated. Please contact support.';
    } elseif (!password_verify($password, $user['password_hash'])) {
        $errors[] = 'Invalid email or password.';
    }

    if (empty($errors)) {
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'verification_status' => $user['verification_status'],
        ];
        $dest = $_SESSION['return_to'] ?? (BASE_URL . '/' . $user['role'] . '/dashboard.php');
        unset($_SESSION['return_to']);
        header('Location: ' . $dest);
        exit;
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="card auth-card">
    <h1>Welcome Back</h1>
    <p style="text-align:center;color:var(--text-muted);">Login to your SkillShare account</p>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="<?= BASE_URL ?>/auth/login.php">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>
    <p class="auth-switch">Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php">Register here</a></p>
    <div class="alert alert-info" style="margin-top:20px;">
      <strong>Demo accounts</strong> (password: <code>Demo@1234</code>):<br>
      student@example.com &middot; teacher@example.com &middot; employer@example.com &middot; admin@example.com
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
