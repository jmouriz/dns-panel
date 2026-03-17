<?php $type='TXT'; $currentType=strtoupper($record['type'] ?? 'A'); $isActive=($currentType===$type); ?>
<div class="record-type-fields" data-type="<?= h($type); ?>" style="<?= $isActive ? '' : 'display:none;'; ?>">
  <label>TXT text</label><textarea name="txt" rows="3" <?= $isActive ? '' : 'disabled'; ?>><?= h($record['txt'] ?? ''); ?></textarea>
</div>
