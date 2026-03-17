<?php
$zoneType = $data['zone_type'] ?? 'Native';
$isSlave = ($zoneType === 'Slave');
?>

<section class="page-actions">
  <h1>Edit zone</h1>
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

<div class="card card-form">
  <form method="post" id="zone-edit-form">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">

    <label>Zone type</label>
    <select name="zone_type" id="zone-type-select" disabled>
      <option value="Native" <?= $zoneType === 'Native' ? 'selected' : ''; ?>>Native</option>
      <option value="Master" <?= $zoneType === 'Master' ? 'selected' : ''; ?>>Master</option>
      <option value="Slave" <?= $zoneType === 'Slave' ? 'selected' : ''; ?>>Slave</option>
    </select>
    <input type="hidden" name="zone_type" value="<?= h($zoneType); ?>">

    <label>Zone name</label>
    <input type="text" name="name" value="<?= h($data['name'] ?? $zone); ?>" disabled>
    <input type="hidden" name="name" value="<?= h($data['name'] ?? $zone); ?>">

    <div id="zone-native-fields" style="<?= $isSlave ? 'display:none;' : ''; ?>">
      <label>Primary NS</label>
      <input
        type="text"
        name="primary_ns"
        value="<?= h($data['primary_ns'] ?? ''); ?>"
        <?= $isSlave ? '' : 'required'; ?>
      >

      <label>Hostmaster</label>
      <input
        type="text"
        name="hostmaster"
        value="<?= h($data['hostmaster'] ?? ''); ?>"
        <?= $isSlave ? '' : 'required'; ?>
      >

      <label class="checkbox-row">
        <input type="checkbox" name="dnssec" value="1" <?= ($data['dnssec'] ?? '0') === '1' ? 'checked' : ''; ?>>
        Enable DNSSEC
      </label>
    </div>

    <div id="zone-slave-fields" style="<?= $isSlave ? '' : 'display:none;'; ?>">
      <label>Masters</label>
      <input
        type="text"
        name="masters"
        value="<?= h($data['masters'] ?? ''); ?>"
        placeholder="192.0.2.10, 198.51.100.20"
        <?= $isSlave ? 'required' : ''; ?>
      >
      <small>Comma-separated list of master IP addresses.</small>
    </div>

    <div class="actions">
      <button type="submit">Save zone</button>
    </div>
  </form>
</div>
