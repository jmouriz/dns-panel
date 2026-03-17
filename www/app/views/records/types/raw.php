<?php $type='RAW'; $currentType=strtoupper($record['type'] ?? 'A'); $isActive=($currentType===$type); ?>
<div class="record-type-fields" data-type="<?= h($type); ?>" style="<?= $isActive ? '' : 'display:none;'; ?>">
  <label>Raw content</label><textarea name="value" rows="3" <?= $isActive ? '' : 'disabled'; ?>><?= h($record['value'] ?? ''); ?></textarea>
</div>
