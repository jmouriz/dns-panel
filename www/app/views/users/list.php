<section class="page-actions"><h1>Users</h1><a class="button" href="index.php?action=users_create">Create user</a></section>
<?php if ($flash): ?><div class="alert alert-<?= h($flash['type']); ?>"><?= h($flash['message']); ?></div><?php endif; ?>
<div class="card">
<table class="table">
<thead><tr><th>Username</th><th>Display name</th><th>Active</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($users as $user): ?>
<tr>
  <td><?= h($user['username']); ?></td>
  <td><?= h($user['display_name']); ?></td>
  <td><?= (int)$user['is_active'] ? 'Yes' : 'No'; ?></td>
  <td class="actions-inline">
    <a class="button small secondary" href="index.php?action=users_edit&id=<?= (int)$user['id']; ?>">Edit</a>
    <a class="button small danger" href="index.php?action=users_delete&id=<?= (int)$user['id']; ?>">Delete</a>
  </td>
</tr>
<?php endforeach; ?>
<?php if (!$users): ?><tr><td colspan="4">No users found.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
