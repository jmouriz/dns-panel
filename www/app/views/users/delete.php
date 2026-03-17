<section class="page-actions"><h1>Delete user</h1><a class="button secondary" href="index.php?action=users">Back</a></section>
<div class="card">
  <p>Delete user <strong><?= h($user['username']); ?></strong>?</p>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">
    <div class="actions"><button class="danger" type="submit">Delete</button></div>
  </form>
</div>
