<h1>PowerDNS connection</h1>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if (!empty($message)): ?><div class="alert alert-success"><?= h($message); ?></div><?php endif; ?>
<form method="post">
  <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">
  <label>PowerDNS URL</label><input type="text" name="url" value="<?= h($data['url'] ?? ''); ?>" required>
  <label>PowerDNS API Key</label><input type="text" name="api_key" value="<?= h($data['api_key'] ?? ''); ?>" required>
  <label>Server ID</label><input type="text" name="server_id" value="<?= h($data['server_id'] ?? 'localhost'); ?>" required>
  <div class="actions"><button type="submit" name="test" value="1">Test</button><button type="submit" name="continue" value="1">Continue</button></div>
</form>
