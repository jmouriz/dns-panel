<h1>Create administrator</h1>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post">
  <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">
  <label>Username</label><input type="text" name="username" value="<?= h($data['username'] ?? 'admin'); ?>" required>
  <label>Display name</label><input type="text" name="display_name" value="<?= h($data['display_name'] ?? 'Administrator'); ?>">
  <label>Password</label><input type="password" name="password" required>
  <label>Confirm password</label><input type="password" name="password_confirm" required>
  <div class="actions"><button type="submit">Install</button></div>
</form>
