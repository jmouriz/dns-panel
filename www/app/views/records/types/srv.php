<?php $type='SRV'; $currentType=strtoupper($record['type'] ?? 'A'); $isActive=($currentType===$type); ?>
<div class="record-type-fields" data-type="<?= h($type); ?>" style="<?= $isActive ? '' : 'display:none;'; ?>">
  <label>Priority</label><input type="number" name="priority" value="<?= h($record['priority'] ?? 0); ?>" <?= $isActive ? '' : 'disabled'; ?>><label>Weight</label><input type="number" name="weight" value="<?= h($record['weight'] ?? 0); ?>" <?= $isActive ? '' : 'disabled'; ?>><label>Port</label><input type="number" name="port" value="<?= h($record['port'] ?? 0); ?>" <?= $isActive ? '' : 'disabled'; ?>><label>Target</label><input type="text" name="target" value="<?= h($record['target'] ?? ''); ?>" <?= $isActive ? '' : 'disabled'; ?>>
</div>
