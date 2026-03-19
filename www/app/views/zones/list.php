<section class="page-actions">
  <h1>Zones</h1>
  <?php if (can(current_user(), 'zones.create')): ?>
    <div class="actions-inline">
      <a class="button" href="index.php?action=zones_create">Create zone</a>
      <a class="button secondary" href="index.php?action=zones_clone">Copy zone</a>
    </div>
  <?php endif; ?>
</section>

<?php if ($flash): ?>
  <div class="alert alert-<?= h($flash['type']); ?>"><?= h($flash['message']); ?></div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= h($error); ?></div>
<?php endif; ?>

<div class="card">
  <table class="table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Kind</th>
        <th>Details</th>
        <th>DNSSEC</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($zones as $zone): ?>
        <?php
          $isSlave = strcasecmp($zone['kind'] ?? '', 'Slave') === 0;
          $masters = $zone['masters'] ?? [];
        ?>
        <tr>
          <td>
            <span class="zone-name-ellipsis" title="<?= h($zone['name']); ?>">
              <?= h($zone['name']); ?>
            </span>
          </td>

          <td><?= h($zone['kind'] ?? ''); ?></td>
          <td>
            <?php if ($isSlave): ?>
              <?php if (!empty($masters)): ?>
                <strong>Masters:</strong> <?= h(implode(', ', $masters)); ?>
              <?php else: ?>
                <span class="muted">No masters reported</span>
              <?php endif; ?>
            <?php else: ?>
              <span class="muted">Local zone</span>
            <?php endif; ?>
          </td>
          <td><?= !empty($zone['dnssec']) ? 'Enabled' : 'Disabled'; ?></td>
          <td class="actions-inline">
            <a class="button small secondary" href="index.php?action=records&zone=<?= urlencode($zone['name']); ?>">
              Records
            </a>

            <a class="button small secondary" href="index.php?action=zones_diag&zone=<?= urlencode($zone['name']); ?>">
              Diagnostics
            </a>

            <?php if (can_access_zone(current_user(), $zone['name']) && can(current_user(), 'zones.create')): ?>
              <a class="button small secondary" href="index.php?action=zones_edit&zone=<?= urlencode($zone['name']); ?>">
                Edit
              </a>
            <?php endif; ?>

            <?php if (!$isSlave && can_access_zone(current_user(), $zone['name']) && can(current_user(), 'soa.edit')): ?>
              <a class="button small secondary" href="index.php?action=zones_soa&zone=<?= urlencode($zone['name']); ?>">
                SOA
              </a>
            <?php endif; ?>

            <?php if (can(current_user(), 'zones.delete')): ?>
              <a class="button small danger" href="index.php?action=zones_delete&zone=<?= urlencode($zone['name']); ?>">
                Delete
              </a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (!$zones): ?>
        <tr>
          <td colspan="5">No zones found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
