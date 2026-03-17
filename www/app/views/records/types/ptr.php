<?php $type='PTR'; $currentType=strtoupper($record['type'] ?? 'A'); $isActive=($currentType===$type); ?>
<div class="record-type-fields" data-type="<?= h($type); ?>" style="<?= $isActive ? '' : 'display:none;'; ?>">
  <label>PTR target</label><input type="text" name="target" value="<?= h($record['target'] ?? ''); ?>" <?= $isActive ? '' : 'disabled'; ?>>
</div>
