<h1>Panel configuration</h1>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">
  <label>Panel title</label><input type="text" name="title" value="<?= h($data['title'] ?? ''); ?>" required>
  <label>Panel subtitle</label><input type="text" name="subtitle" value="<?= h($data['subtitle'] ?? ''); ?>">
  <label>Default theme</label>
  <select name="default_theme">
    <option value="auto" <?= ($data['default_theme'] ?? 'auto') === 'auto' ? 'selected' : ''; ?>>Auto</option>
    <option value="light" <?= ($data['default_theme'] ?? '') === 'light' ? 'selected' : ''; ?>>Light</option>
    <option value="dark" <?= ($data['default_theme'] ?? '') === 'dark' ? 'selected' : ''; ?>>Dark</option>
  </select>
  <label>User can change theme</label>
  <select name="allow_user_theme_override">
    <option value="1" <?= ($data['allow_user_theme_override'] ?? '1') === '1' ? 'selected' : ''; ?>>Yes</option>
    <option value="0" <?= ($data['allow_user_theme_override'] ?? '') === '0' ? 'selected' : ''; ?>>No</option>
  </select>
  <label>Logo (optional)</label><input type="file" name="logo" accept="image/*">
  <div class="actions"><button type="submit">Continue</button></div>
</form>
