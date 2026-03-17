<?php
$zoneType = $data['zone_type'] ?? 'Native';
?>
<section class="page-actions">
  <h1>Create zone</h1>
  <a class="button secondary" href="index.php?action=zones">Back</a>
</section>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?>
      <div><?= h($e); ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card card-form">
  <form method="post" id="zone-create-form">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">

    <label>Zone type</label>
    <select name="zone_type" id="zone-type-select">
      <option value="Native" <?= $zoneType === 'Native' ? 'selected' : ''; ?>>Native</option>
      <option value="Master" <?= $zoneType === 'Master' ? 'selected' : ''; ?>>Master</option>
      <option value="Slave" <?= $zoneType === 'Slave' ? 'selected' : ''; ?>>Slave</option>
    </select>

    <label>Zone name</label>
    <input type="text" name="name" value="<?= h($data['name'] ?? ''); ?>" required>

    <div id="zone-native-fields" style="<?= $zoneType !== 'Slave' ? '' : 'display:none;'; ?>">
      <label>Primary NS</label>
      <input
        type="text"
        name="primary_ns"
        value="<?= h($data['primary_ns'] ?? 'ns1'); ?>"
        <?= $zoneType === 'Native' ? 'required' : ''; ?>
      >

      <label>Hostmaster</label>
      <input
        type="text"
        name="hostmaster"
        value="<?= h($data['hostmaster'] ?? ''); ?>"
        <?= $zoneType === 'Native' ? 'required' : ''; ?>
      >

      <label class="checkbox-row">
        <input type="checkbox" name="dnssec" value="1" <?= ($data['dnssec'] ?? '0') === '1' ? 'checked' : ''; ?>>
        Enable DNSSEC on creation
      </label>
    </div>

    <div id="zone-slave-fields" style="<?= $zoneType === 'Slave' ? '' : 'display:none;'; ?>">
      <label>Masters</label>
      <input
        type="text"
        name="masters"
        value="<?= h($data['masters'] ?? ''); ?>"
        placeholder="192.0.2.10, 198.51.100.20"
        <?= $zoneType === 'Slave' ? 'required' : ''; ?>
      >
      <small>Comma-separated list of master IP addresses.</small>
    </div>

    <div class="actions">
      <button type="submit">Create zone</button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const typeSelect = document.getElementById('zone-type-select');
  const nativeFields = document.getElementById('zone-native-fields');
  const slaveFields = document.getElementById('zone-slave-fields');

  if (!typeSelect || !nativeFields || !slaveFields) return;

  function syncZoneType() {
    const isNotSlave = typeSelect.value !== 'Slave';
    //const isNative = typeSelect.value === 'Native';
    nativeFields.style.display = isNotSlave ? '' : 'none';
    slaveFields.style.display = isNotSlave ? 'none' : '';

    nativeFields.querySelectorAll('input').forEach(function (el) {
      if (el.name === 'primary_ns' || el.name === 'hostmaster') {
        el.required = isNotSlave;
      }
    });

    slaveFields.querySelectorAll('input').forEach(function (el) {
      if (el.name === 'masters') {
        el.required = !isNotSlave;
      }
    });
  }

  typeSelect.addEventListener('change', syncZoneType);
  syncZoneType();
});
</script>
