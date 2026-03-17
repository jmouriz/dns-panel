<?php $title = $mode === 'edit' ? 'Edit user' : 'Create user'; ?>
<section class="page-actions"><h1><?= h($title); ?></h1><a class="button secondary" href="index.php?action=users">Back</a></section>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
<div class="card card-form">
<form method="post">
  <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">
  <label>Username</label><input type="text" name="username" value="<?= h($data['username'] ?? ''); ?>" required>
  <label>Display name</label><input type="text" name="display_name" value="<?= h($data['display_name'] ?? ''); ?>">
  <label>Theme preference</label>
  <select name="theme_preference">
    <?php foreach (['auto','light','dark'] as $theme): ?><option value="<?= h($theme); ?>" <?= ($data['theme_preference'] ?? 'auto') === $theme ? 'selected' : ''; ?>><?= h(ucfirst($theme)); ?></option><?php endforeach; ?>
  </select>
  <label>Active</label>
  <select name="is_active">
    <option value="1" <?= ($data['is_active'] ?? '1') === '1' ? 'selected' : ''; ?>>Yes</option>
    <option value="0" <?= ($data['is_active'] ?? '') === '0' ? 'selected' : ''; ?>>No</option>
  </select>
  <label>Password <?= $mode === 'edit' ? '(leave blank to keep current)' : ''; ?></label><input type="password" name="password" <?= $mode === 'create' ? 'required' : ''; ?>>
  <label>Confirm password</label><input type="password" name="password_confirm" <?= $mode === 'create' ? 'required' : ''; ?>>
  <fieldset><legend>Roles</legend><?php foreach ($roles as $role): ?><label class="checkbox-row"><input type="checkbox" name="roles[]" value="<?= (int)$role['id']; ?>" <?= in_array((int)$role['id'], $data['roles'] ?? [], true) ? 'checked' : ''; ?>><?= h($role['name']); ?></label><?php endforeach; ?></fieldset>
  <fieldset><legend>Zone access</legend><?php foreach ($zones as $zone): ?><label class="checkbox-row"><input type="checkbox" name="zones[]" value="<?= h($zone['name']); ?>" <?= in_array($zone['name'], $data['zones'] ?? [], true) ? 'checked' : ''; ?>><?= h($zone['name']); ?></label><?php endforeach; ?></fieldset>
  <div class="actions"><button type="submit"><?= $mode === 'edit' ? 'Save user' : 'Create user'; ?></button></div>
</form>
</div>
