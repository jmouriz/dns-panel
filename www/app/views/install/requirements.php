<h1>Requirements</h1>
<ul class="requirements">
<?php foreach ($requirements as $k => $v): ?><li class="<?= $v ? 'ok' : 'bad'; ?>"><strong><?= h($k); ?></strong>: <?= $v ? 'OK' : 'Missing'; ?></li><?php endforeach; ?>
</ul>
<?php if ($ok): ?><p><a class="button" href="install.php?step=panel">Continue</a></p><?php else: ?><div class="alert alert-danger">Fix requirements first.</div><?php endif; ?>
