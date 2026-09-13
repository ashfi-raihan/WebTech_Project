<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    redirect('/' . currentUser()['role'] . '/dashboard.php');
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';
    $company_name = trim($_POST['company_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!in_array($role, ['student', 'teacher', 'employer'], true)) $errors[] = 'Please select a valid role.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'An account with this email already exists.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, phone, verification_status) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hash, $role, $phone ?: null, 'unverified']);
            $userId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO user_profiles (user_id, company_name) VALUES (?, ?)');
            $stmt->execute([$userId, $role === 'employer' ? ($company_name ?: null) : null]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong creating your account. Please try again.';
        }

        if (empty($errors)) {
            $_SESSION['user'] = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'verification_status' => 'unverified',
            ];
            redirect('/' . $role . '/dashboard.php');
        }
    }
}

$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="card auth-card">
    <h1>Create an Account</h1>
    <p style="text-align:center;color:var(--text-muted);">Join the Skill-Sharing community</p>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="POST" action="<?= BASE_URL ?>/auth/register.php">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" required value="<?= e($old['name'] ?? '') ?>" placeholder="Jane Doe">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required value="<?= e($old['email'] ?? '') ?>" placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="tel" name="phone" value="<?= e($old['phone'] ?? '') ?>" placeholder="01700000000">
      </div>
      <div class="form-group">
        <label>I am a...</label>
        <select name="role" id="role-select" required>
          <option value="">Select a role</option>
          <option value="student" <?= ($old['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student (want to learn)</option>
          <option value="teacher" <?= ($old['role'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher (want to teach)</option>
          <option value="employer" <?= ($old['role'] ?? '') === 'employer' ? 'selected' : '' ?>>Employer (want to hire)</option>
        </select>
      </div>
      <div class="form-group" id="company-group" style="<?= ($old['role'] ?? '') === 'employer' ? '' : 'display:none;' ?>">
        <label>Company Name (Employers)</label>
        <input type="text" name="company_name" value="<?= e($old['company_name'] ?? '') ?>" placeholder="Acme Inc.">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required placeholder="At least 6 characters">
        </div>
        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" required placeholder="Repeat password">
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>
    <p class="auth-switch">Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Login here</a></p>
  </div>
</div>
<script>
  const roleSelect = document.getElementById('role-select');
  const companyGroup = document.getElementById('company-group');
  roleSelect.addEventListener('change', () => {
    companyGroup.style.display = roleSelect.value === 'employer' ? 'block' : 'none';
  });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
