<?php $type='MX'; $currentType=strtoupper($record['type'] ?? 'A'); $isActive=($currentType===$type); ?>
<div class="record-type-fields" data-type="<?= h($type); ?>" style="<?= $isActive ? '' : 'display:none;'; ?>">
  <label>Priority</label><input type="number" name="priority" value="<?= h($record['priority'] ?? 10); ?>" <?= $isActive ? '' : 'disabled'; ?>><label>Mail target</label><input type="text" name="target" value="<?= h($record['target'] ?? ''); ?>" <?= $isActive ? '' : 'disabled'; ?>>
</div>
