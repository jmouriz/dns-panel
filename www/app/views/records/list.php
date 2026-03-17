<section class="page-actions">
  <h1>
    Records - 
    <span class="zone-name-ellipsis heading" title="<?= h($data['name'] ?? $zone); ?>">
      <?= h($data['name'] ?? $zone); ?>
    </span>
  </h1>
  <div class="actions-inline">
    <?php if (!$isSlave && can_access_zone(current_user(), $zone) && can(current_user(), 'records.edit')): ?>
      <a class="button" href="index.php?action=records_add&zone=<?= urlencode($zone); ?>">Add record</a>
    <?php endif; ?>
    <a class="button secondary" href="index.php?action=zones">Back</a>
  </div>
</section>

<?php if ($flash): ?>
  <div class="alert alert-<?= h($flash['type']); ?>"><?= h($flash['message']); ?></div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= h($error); ?></div>
<?php endif; ?>

<?php if ($isSlave): ?>
  <div class="alert alert-warning">This is a slave zone. Records are read-only.</div>
<?php endif; ?>

<div class="card">
  <table class="table">
    <thead>
      <tr>
        <th>Name</th>
        <th>TTL</th>
        <th>Type</th>
        <th>Content</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($records as $record): ?>
        <?php if (strtoupper($record['type']) === 'SOA') continue; ?>
        <tr>
          <td><?= h($record['name']); ?></td>
          <td><?= h($record['ttl']); ?></td>
          <td><?= h($record['type']); ?></td>
          <td>
            <span class="content-ellipsis" title="<?=h($record['content'])?>">
              <?= h($record['content']); ?>
            </span>
          </td>
          <td class="actions-inline">
            <?php if (!$isSlave && can_access_zone(current_user(), $zone) && can(current_user(), 'records.edit')): ?>
              <a class="button small secondary" href="index.php?action=records_edit&zone=<?= urlencode($zone); ?>&name=<?= urlencode($record['name']); ?>&type=<?= urlencode($record['type']); ?>&ttl=<?= urlencode((string)$record['ttl']); ?>&content=<?= urlencode($record['content']); ?>">
                Edit
              </a>
              <a class="button small danger" href="index.php?action=records_delete&zone=<?= urlencode($zone); ?>&name=<?= urlencode($record['name']); ?>&type=<?= urlencode($record['type']); ?>">
                Delete
              </a>
            <?php else: ?>
              <span class="muted">Read-only</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (!$records): ?>
        <tr>
          <td colspan="5">No records found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
