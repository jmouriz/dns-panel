<section class="card card-narrow">
  <h1>Login</h1>
  <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= h($e); ?></div><?php endforeach; ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()); ?>">
    <label>Username</label><input type="text" name="username" required autofocus>
    <label>Password</label><input type="password" name="password" required>
    <div class="actions"><button type="submit">Login</button></div>
  </form>
</section>
