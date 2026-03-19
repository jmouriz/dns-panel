<section class="page-actions">
  <h1>Copy zone</h1>
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
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">

    <label>Source zone</label>
    <select name="source_zone" required>
      <option value="">Select a zone</option>
      <?php foreach ($zones as $zone): ?>
        <option value="<?= h($zone['name']); ?>" <?= ($data['source_zone'] ?? '') === ($zone['name'] ?? '') ? 'selected' : ''; ?>>
          <?= h($zone['name']); ?><?= !empty($zone['kind']) ? ' (' . h($zone['kind']) . ')' : ''; ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Target zone</label>
    <input type="text" name="target_zone" value="<?= h($data['target_zone'] ?? ''); ?>" placeholder="tecnologica.com.ar" required>

    <div class="actions">
      <button type="submit">Copy zone</button>
    </div>
  </form>
</div>
