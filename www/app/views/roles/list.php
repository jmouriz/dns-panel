<section class="page-actions"><h1>Roles</h1></section>
<div class="grid two">
<?php foreach ($map as $item): ?>
  <div class="card">
    <h2><?= h($item['role']['name']); ?></h2>
    <ul class="stack-list">
      <?php foreach ($item['permissions'] as $permission): ?><li><?= h($permission); ?></li><?php endforeach; ?>
      <?php if (!$item['permissions']): ?><li>No permissions</li><?php endif; ?>
    </ul>
  </div>
<?php endforeach; ?>
</div>
