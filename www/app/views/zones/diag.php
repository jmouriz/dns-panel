<section class="page-actions">
  <h1>
    Diagnostics -
    <span class="zone-name-ellipsis heading" title="<?= h($data['name'] ?? $zone); ?>">
      <?= h($data['name'] ?? $zone); ?>
    </span>
  </h1>
  <a class="button secondary" href="index.php?action=zones">Back</a>
</section>

<?php if (!empty($flash)): ?>
  <div class="alert alert-<?= h($flash['type']); ?>"><?= h($flash['message']); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?>
      <div><?= h($e); ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="grid two">
  <div class="card card-form">
    <h2>Query</h2>

    <form method="post">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">

      <label>Server</label>
      <select name="server_mode" id="diag-server-mode">
        <?php foreach ($servers as $serverKey => $serverIp): ?>
          <option value="<?= h($serverKey); ?>" <?= ($data['server_mode'] ?? 'localhost') === $serverKey ? 'selected' : ''; ?>>
            <?= h($serverKey); ?><?= $serverIp !== '' ? ' (' . h($serverIp) . ')' : ''; ?>
          </option>
        <?php endforeach; ?>
        <option value="custom" <?= ($data['server_mode'] ?? '') === 'custom' ? 'selected' : ''; ?>>custom</option>
      </select>

      <div id="diag-custom-server-wrapper" style="<?= ($data['server_mode'] ?? '') === 'custom' ? '' : 'display:none;'; ?>">
        <label>Custom server IP / host</label>
        <input type="text" name="custom_server" value="<?= h($data['custom_server'] ?? ''); ?>" placeholder="192.0.2.10">
      </div>

      <label>Name</label>
      <input type="text" name="name" value="<?= h($data['name'] ?? '@'); ?>" placeholder="@">

      <label>Type</label>
      <select name="type">
        <?php foreach ($types as $type): ?>
          <option value="<?= h($type); ?>" <?= ($data['type'] ?? 'SOA') === $type ? 'selected' : ''; ?>>
            <?= h($type); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <div class="actions">
        <button type="submit" name="query" value="1">Query</button>
        <button type="submit" name="compare_soa" value="1" class="secondary">Compare SOA master/slave</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h2>Quick notes</h2>
    <ul class="stack-list">
      <li><strong>Name</strong>: use <code>@</code> for the zone apex.</li>
      <li><strong>TLSA</strong>: common names look like <code>_443._tcp</code>.</li>
      <li><strong>SOA compare</strong>: needs <code>dns.master_ip</code> and <code>dns.slave_ip</code> in panel settings.</li>
    </ul>
  </div>
</div>

<?php if ($result): ?>
  <div class="card">
    <h2>Query result</h2>

    <dl class="keyvals">
      <dt>Command</dt>
      <dd><code><?= h($result['command'] ?? ''); ?></code></dd>

      <dt>Exit code</dt>
      <dd><?= h((string)($result['exit_code'] ?? '')); ?></dd>
    </dl>

    <?php if ($soaParsed): ?>
      <h3>Parsed SOA</h3>
      <dl class="keyvals">
        <dt>MNAME</dt><dd><?= h($soaParsed['mname'] ?? ''); ?></dd>
        <dt>RNAME</dt><dd><?= h($soaParsed['rname'] ?? ''); ?></dd>
        <dt>Serial</dt><dd><?= h($soaParsed['serial'] ?? ''); ?></dd>
        <dt>Refresh</dt><dd><?= h($soaParsed['refresh'] ?? ''); ?></dd>
        <dt>Retry</dt><dd><?= h($soaParsed['retry'] ?? ''); ?></dd>
        <dt>Expire</dt><dd><?= h($soaParsed['expire'] ?? ''); ?></dd>
        <dt>Minimum</dt><dd><?= h($soaParsed['minimum'] ?? ''); ?></dd>
      </dl>
    <?php endif; ?>

    <h3>Raw output</h3>
    <pre class="diag-output"><?= h($result['output'] ?? ''); ?></pre>
  </div>
<?php endif; ?>

<?php if ($compare): ?>
  <div class="card">
    <h2>SOA comparison</h2>

    <div class="alert alert-<?= !empty($compare['in_sync']) ? 'success' : 'danger'; ?>">
      <?= !empty($compare['in_sync']) ? 'Master and slave are in sync.' : 'Master and slave are NOT in sync.'; ?>
    </div>

    <div class="grid two">
      <div>
        <h3>Master (<?= h($compare['master_ip'] ?? ''); ?>)</h3>
        <?php if (!empty($compare['master_soa'])): ?>
          <dl class="keyvals">
            <dt>MNAME</dt><dd><?= h($compare['master_soa']['mname'] ?? ''); ?></dd>
            <dt>RNAME</dt><dd><?= h($compare['master_soa']['rname'] ?? ''); ?></dd>
            <dt>Serial</dt><dd><?= h($compare['master_soa']['serial'] ?? ''); ?></dd>
          </dl>
        <?php endif; ?>
        <pre class="diag-output"><?= h($compare['master_result']['output'] ?? ''); ?></pre>
      </div>

      <div>
        <h3>Slave (<?= h($compare['slave_ip'] ?? ''); ?>)</h3>
        <?php if (!empty($compare['slave_soa'])): ?>
          <dl class="keyvals">
            <dt>MNAME</dt><dd><?= h($compare['slave_soa']['mname'] ?? ''); ?></dd>
            <dt>RNAME</dt><dd><?= h($compare['slave_soa']['rname'] ?? ''); ?></dd>
            <dt>Serial</dt><dd><?= h($compare['slave_soa']['serial'] ?? ''); ?></dd>
          </dl>
        <?php endif; ?>
        <pre class="diag-output"><?= h($compare['slave_result']['output'] ?? ''); ?></pre>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const mode = document.getElementById('diag-server-mode');
  const customWrapper = document.getElementById('diag-custom-server-wrapper');

  function syncServerMode() {
    if (!mode || !customWrapper) return;
    customWrapper.style.display = mode.value === 'custom' ? '' : 'none';
  }

  if (mode) {
    mode.addEventListener('change', syncServerMode);
    syncServerMode();
  }
});
</script>
