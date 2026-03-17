<section class="page-actions">
  <h1>Configuration</h1>
</section>

<?php if ($success): ?>
  <div class="alert alert-success"><?= h($success); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?>
      <div><?= h($e); ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card card-form">
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">

    <label>Panel title</label>
    <input type="text" name="panel_title" value="<?= h($data['panel.title']); ?>" required>

    <label>Panel subtitle</label>
    <input type="text" name="panel_subtitle" value="<?= h($data['panel.subtitle']); ?>">

    <label>Default theme</label>
    <select name="ui_default_theme">
      <?php foreach (['auto','light','dark'] as $theme): ?>
        <option value="<?= h($theme); ?>" <?= $data['ui.default_theme'] === $theme ? 'selected' : ''; ?>>
          <?= h(ucfirst($theme)); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>User can change theme</label>
    <select name="ui_allow_user_theme_override">
      <option value="1" <?= $data['ui.allow_user_theme_override'] === '1' ? 'selected' : ''; ?>>Yes</option>
      <option value="0" <?= $data['ui.allow_user_theme_override'] === '0' ? 'selected' : ''; ?>>No</option>
    </select>

    <label>Logo</label>
    <input type="file" name="panel_logo" accept="image/*">

    <label>PowerDNS URL</label>
    <input type="text" name="pdns_url" value="<?= h($data['pdns.url']); ?>" required>

    <label>PowerDNS API key</label>
    <input type="text" name="pdns_api_key" value="<?= h($data['pdns.api_key']); ?>" required>

    <label>PowerDNS server ID</label>
    <input type="text" name="pdns_server_id" value="<?= h($data['pdns.server_id']); ?>" required>

    <label>Master DNS IP</label>
    <input type="text" name="dns_master_ip" value="<?= h($data['dns.master_ip']); ?>" placeholder="170.78.75.50">

    <label>Slave DNS IP</label>
    <input type="text" name="dns_slave_ip" value="<?= h($data['dns.slave_ip']); ?>" placeholder="170.78.75.49">

    <div class="actions">
      <button type="submit">Save configuration</button>
    </div>
  </form>
</div>
