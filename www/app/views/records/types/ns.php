<?php $type='NS'; $currentType=strtoupper($record['type'] ?? 'A'); $isActive=($currentType===$type); ?>
<div class="record-type-fields" data-type="<?= h($type); ?>" style="<?= $isActive ? '' : 'display:none;'; ?>">
  <label>Target nameserver</label><input type="text" name="target" value="<?= h($record['target'] ?? ''); ?>" <?= $isActive ? '' : 'disabled'; ?>>
</div>
