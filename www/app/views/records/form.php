<?php
$action = $mode === 'edit' ? 'records_edit' : 'records_add';
$query = 'index.php?action=' . $action . '&zone=' . urlencode($zone);
if ($mode === 'edit') {
    $query .= '&name=' . urlencode($record['original_name']) . '&type=' . urlencode($record['original_type']) . '&content=' . urlencode($record['original_content'] ?? '') . '&ttl=' . urlencode((string)($record['ttl'] ?? 3600));
}
?>
<section class="page-actions"><h1><?= $mode === 'edit' ? 'Edit record' : 'Add record'; ?> - <?= h($zone); ?></h1><a class="button secondary" href="index.php?action=records&zone=<?= urlencode($zone); ?>">Back</a></section>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
<div class="card card-form">
<form method="post" action="<?= h($query); ?>">
  <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">
  <label>Name</label><input type="text" name="name" value="<?= h($record['name'] ?? '@'); ?>">
  <label>TTL</label><input type="number" name="ttl" value="<?= h((string)($record['ttl'] ?? 3600)); ?>">
  <label>Type</label>
  <select name="type" id="record-type-select">
    <?php foreach (['A','AAAA','NS','MX','CNAME','TXT','PTR','SRV','TLSA','RAW'] as $type): ?>
      <option value="<?= h($type); ?>" <?= strtoupper($record['type'] ?? 'A') === $type ? 'selected' : ''; ?>><?= h($type); ?></option>
    <?php endforeach; ?>
  </select>
  <div id="record-type-fields-container">
    <?php foreach (['a','aaaa','ns','mx','cname','txt','ptr','srv','tlsa','raw'] as $partial) require APP_DIR . '/views/records/types/' . $partial . '.php'; ?>
  </div>
  <div class="actions"><button type="submit"><?= $mode === 'edit' ? 'Save record' : 'Create record'; ?></button></div>
</form>
</div>
