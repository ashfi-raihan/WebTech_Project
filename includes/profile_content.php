<?php
// Expects $user, $profile, $skills, $ratingRow, $verification, $allSkills to be set by including page.
$role = $user['role'];
?>
<?php include __DIR__ . '/alerts.php'; ?>

<div class="card" style="margin-bottom:24px;display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
  <div class="avatar-circle"><?= e(mb_substr($user['name'], 0, 1)) ?></div>
  <div>
    <h2 style="margin-bottom:2px;"><?= e($user['name']) ?></h2>
    <p class="help-text" style="margin:0;"><?= e($user['email']) ?> &middot; <?= e(ucfirst($role)) ?></p>
    <div style="margin-top:6px;">
      <?php if ($user['verification_status'] === 'approved'): ?><span class="badge badge-success">✓ Verified</span>
      <?php elseif ($user['verification_status'] === 'pending'): ?><span class="badge badge-warning">Verification Pending</span>
      <?php elseif ($user['verification_status'] === 'rejected'): ?><span class="badge badge-danger">Verification Rejected</span>
      <?php else: ?><span class="badge badge-muted">Not Verified</span><?php endif; ?>
      <span class="badge badge-info">★ <?= number_format((float)$ratingRow['avg_rating'], 1) ?> (<?= (int)$ratingRow['review_count'] ?> reviews)</span>
    </div>
  </div>
</div>

<?php if (in_array($user['verification_status'], ['unverified', 'rejected'], true)): ?>
<div class="card" style="margin-bottom:24px;">
  <h3>Get Verified</h3>
  <?php if ($verification && $verification['status'] === 'rejected'): ?>
    <p class="help-text">Your last request was rejected. Reason: <?= e($verification['admin_notes'] ?? 'No reason given.') ?></p>
  <?php endif; ?>
  <form method="POST" action="<?= BASE_URL ?>/<?= e($role) ?>/profile_action.php">
    <input type="hidden" name="action" value="verify">
    <div class="form-group">
      <label>Document / Credential Info</label>
      <textarea name="document_info" placeholder="Describe your ID, certificates, or portfolio for verification..."></textarea>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Submit Verification Request</button>
  </form>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:24px;">
  <h3>Edit Profile</h3>
  <form method="POST" action="<?= BASE_URL ?>/<?= e($role) ?>/profile_action.php">
    <input type="hidden" name="action" value="update_profile">
    <div class="form-row">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" value="<?= e($user['name']) ?>" required>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label>Headline</label>
      <input type="text" name="headline" value="<?= e($profile['headline'] ?? '') ?>" placeholder="e.g. Senior Web Developer">
    </div>
    <div class="form-group">
      <label>Bio</label>
      <textarea name="bio" placeholder="Tell others about yourself..."><?= e($profile['bio'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Location</label>
        <input type="text" name="location" value="<?= e($profile['location'] ?? '') ?>">
      </div>
      <?php if ($role === 'employer'): ?>
        <div class="form-group">
          <label>Company Name</label>
          <input type="text" name="company_name" value="<?= e($profile['company_name'] ?? '') ?>">
        </div>
      <?php endif; ?>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Education</label>
        <input type="text" name="education" value="<?= e($profile['education'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Experience</label>
        <input type="text" name="experience" value="<?= e($profile['experience'] ?? '') ?>">
      </div>
    </div>
    <div class="form-group">
      <label>Interests</label>
      <input type="text" name="interests" value="<?= e($profile['interests'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-primary">Save Changes</button>
  </form>
</div>

<?php if ($role !== 'employer'): ?>
<div class="card" style="margin-bottom:24px;">
  <h3>My Skills</h3>
  <?php if (empty($skills)): ?><p class="help-text">No skills added yet.</p><?php endif; ?>
  <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
    <?php foreach ($skills as $s): ?>
      <span class="badge badge-info">
        <?= e($s['skill_name']) ?> (<?= e($s['proficiency']) ?>)
        <form method="POST" action="<?= BASE_URL ?>/<?= e($role) ?>/profile_action.php" style="display:inline;">
          <input type="hidden" name="action" value="remove_skill">
          <input type="hidden" name="user_skill_id" value="<?= (int)$s['id'] ?>">
          <button type="submit" style="border:none;background:none;color:inherit;cursor:pointer;font-weight:700;">&times;</button>
        </form>
      </span>
    <?php endforeach; ?>
  </div>
  <form method="POST" action="<?= BASE_URL ?>/<?= e($role) ?>/profile_action.php" class="form-row">
    <input type="hidden" name="action" value="add_skill">
    <div class="form-group">
      <select name="skill_id" required>
        <?php foreach ($allSkills as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <select name="proficiency">
        <option value="beginner">Beginner</option>
        <option value="intermediate" selected>Intermediate</option>
        <option value="advanced">Advanced</option>
        <option value="expert">Expert</option>
      </select>
    </div>
    <div class="form-group" style="flex:0;">
      <button type="submit" class="btn btn-primary">Add Skill</button>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <h3>Change Password</h3>
  <form method="POST" action="<?= BASE_URL ?>/<?= e($role) ?>/profile_action.php">
    <input type="hidden" name="action" value="change_password">
    <div class="form-row">
      <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
      <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
      <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_new_password" required></div>
    </div>
    <button type="submit" class="btn btn-outline">Update Password</button>
  </form>
</div>
