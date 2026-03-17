<section class="page-actions"><h1>Delete record</h1><a class="button secondary" href="index.php?action=records&zone=<?= urlencode($zone); ?>">Back</a></section>
<?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
<div class="card">
  <p>Delete <strong><?= h($type); ?></strong> record <strong><?= h($name); ?></strong> from zone <strong><?= h($zone); ?></strong>?</p>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">
    <div class="actions"><button class="danger" type="submit">Delete</button></div>
  </form>
</div>
