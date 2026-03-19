<?php $pdnsUrl = rtrim(settings_get('pdns.url', ''), '/'); ?>
<?php if ($flash): ?><div class="alert alert-<?= h($flash['type']); ?>"><?= h($flash['message']); ?></div><?php endif; ?>
<section class="grid two">
  <div class="card">
    <h2>PowerDNS</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= h($error); ?></div>
    <?php else: ?>
      <dl class="keyvals">
        <dt>Server ID</dt><dd><?= h($server['id'] ?? ''); ?></dd>
        <dt>Type</dt><dd><?= h($server['daemon_type'] ?? ''); ?></dd>
        <dt>Version</dt><dd><?= h($server['version'] ?? ''); ?></dd>
        <dt>Zones</dt><dd><?= count($zones); ?></dd>
      </dl>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2>Quick links</h2>
    <div class="stack">
      <a class="button secondary" href="index.php?action=zones">Manage zones</a>
      <?php if (can(current_user(), 'zones.create')): ?><a class="button secondary" href="index.php?action=zones_create">Create zone</a><?php endif; ?>
      <?php if (can(current_user(), 'users.manage')): ?><a class="button secondary" href="index.php?action=users">Manage users</a><?php endif; ?>
    </div>
  </div>
</section>
<?php if ($pdnsUrl !== ''): ?>
<section class="card">
  <h2>PowerDNS status</h2>
  <iframe src="<?= h($pdnsUrl); ?>/"
    title="PowerDNS Web UI"
    style="width:100%; height:700px; border:0; display:block;"
    loading="lazy" referrerpolicy="no-referrer">
  </iframe>
</section>
<?php endif; ?>
