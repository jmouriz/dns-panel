<section class="page-actions"><h1>Change password</h1><a class="button secondary" href="index.php?action=dashboard">Back</a></section>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
<div class="card card-form">
<form method="post">
  <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">
  <label>New password</label><input type="password" name="password" required>
  <label>Confirm password</label><input type="password" name="password_confirm" required>
  <div class="actions"><button type="submit">Save password</button></div>
</form>
</div>
