<section class="page-actions"><h1>SOA - <?= h($zone); ?></h1><a class="button secondary" href="index.php?action=zones">Back</a></section>
<?php if ($flash): ?><div class="alert alert-<?= h($flash['type']); ?>"><?= h($flash['message']); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
<div class="card card-form">
<form method="post">
  <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">
  <label>Primary NS</label><input type="text" name="primary_ns" value="<?= h($soa['primary_ns'] ?? ''); ?>" required>
  <label>Hostmaster</label><input type="text" name="hostmaster" value="<?= h($soa['hostmaster'] ?? ''); ?>" required>
  <label>Serial</label><input type="text" name="serial" value="<?= h($soa['serial'] ?? '1'); ?>" required>
  <label>Refresh</label><input type="text" name="refresh" value="<?= h($soa['refresh'] ?? '10800'); ?>" required>
  <label>Retry</label><input type="text" name="retry" value="<?= h($soa['retry'] ?? '3600'); ?>" required>
  <label>Expire</label><input type="text" name="expire" value="<?= h($soa['expire'] ?? '604800'); ?>" required>
  <label>Minimum</label><input type="text" name="minimum" value="<?= h($soa['minimum'] ?? '3600'); ?>" required>
  <div class="actions"><button type="submit">Save SOA</button></div>
</form>
</div>
