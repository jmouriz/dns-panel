<?php $type='A'; $currentType=strtoupper($record['type'] ?? 'A'); $isActive=($currentType===$type); ?>
<div class="record-type-fields" data-type="<?= h($type); ?>" style="<?= $isActive ? '' : 'display:none;'; ?>">
  <label>IPv4 address</label><input type="text" name="value" value="<?= h($record['value'] ?? ''); ?>" <?= $isActive ? '' : 'disabled'; ?>>
</div>
