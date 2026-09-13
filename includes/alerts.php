<?php
$__errors = getFlashErrors();
$__success = getFlashSuccess();
?>
<?php if (!empty($__errors)): ?>
  <div class="alert alert-error">
    <ul>
      <?php foreach ($__errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
<?php if ($__success): ?>
  <div class="alert alert-success"><?= e($__success) ?></div>
<?php endif; ?>
