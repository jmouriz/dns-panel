<?php
$type = 'TLSA';
$currentType = strtoupper($record['type'] ?? 'A');
$isActive = ($currentType === $type);
?>
<div class="record-type-fields" data-type="<?= h($type); ?>" style="<?= $isActive ? '' : 'display:none;'; ?>">
  <label>Usage</label>
  <input type="text" name="tlsa_usage" value="<?= h($record['tlsa_usage'] ?? ''); ?>" <?= $isActive ? '' : 'disabled'; ?>>

  <label>Selector</label>
  <input type="text" name="tlsa_selector" value="<?= h($record['tlsa_selector'] ?? ''); ?>" <?= $isActive ? '' : 'disabled'; ?>>

  <label>Matching type</label>
  <input type="text" name="tlsa_matching_type" value="<?= h($record['tlsa_matching_type'] ?? ''); ?>" <?= $isActive ? '' : 'disabled'; ?>>

  <label>Certificate association data</label>
  <textarea name="tlsa_cert_data" rows="4" <?= $isActive ? '' : 'disabled'; ?>><?= h($record['tlsa_cert_data'] ?? ''); ?></textarea>
</div>
