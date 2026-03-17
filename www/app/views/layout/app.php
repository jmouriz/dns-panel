<?php
$user = current_user();
$settings = panel_is_installed() ? settings_get_all() : [];
$title = $settings['panel.title'] ?? 'DNS Control Panel';
$subtitle = $settings['panel.subtitle'] ?? '';
$logo = $settings['panel.logo'] ?? '';
$themeDefault = $settings['ui.default_theme'] ?? 'auto';
$allowUserThemeOverride = ($settings['ui.allow_user_theme_override'] ?? '1') === '1';
$userTheme = $user['theme_preference'] ?? 'auto';
$theme = $user['theme_preference'] ?? $themeDefault;

if (!$allowUserThemeOverride) {
    $theme = $themeDefault;
} elseif ($userTheme === 'auto' || $userTheme === '' || $userTheme === null) {
    $theme = $themeDefault;
} else {
    $theme = $userTheme;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= h($theme); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title); ?></title>
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/forms.css">
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
<header class="topbar">
  <div class="brand">
    <?php if ($logo): ?><img src="<?= h($logo); ?>" class="brand-logo" alt="Logo"><?php endif; ?>
    <div>
      <div class="brand-title"><?= h($title); ?></div>
      <?php if ($subtitle): ?><div class="brand-subtitle"><?= h($subtitle); ?></div><?php endif; ?>
    </div>
  </div>
  <?php if ($user): ?>
  <nav class="topnav">
    <a href="index.php?action=dashboard">Dashboard</a>
    <a href="index.php?action=zones">Zones</a>
    <?php if (can($user, 'users.manage')): ?><a href="index.php?action=users">Users</a><?php endif; ?>
    <?php if (can($user, 'roles.manage')): ?><a href="index.php?action=roles">Roles</a><?php endif; ?>
    <?php if (can($user, 'config.manage')): ?><a href="index.php?action=config">Configuration</a><?php endif; ?>
    <a href="index.php?action=users_password">Password</a>
    <a href="index.php?action=logout">Logout</a>
  </nav>
  <?php endif; ?>
</header>
<main class="container">
  <?= $content; ?>
</main>
<script src="assets/js/theme.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/records.js"></script>
</body>
</html>
