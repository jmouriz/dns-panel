<?php
date_default_timezone_set('UTC');

define('APP_ROOT', dirname(__DIR__));
define('APP_DIR', APP_ROOT . '/app');
define('STORAGE_DIR', APP_ROOT . '/storage');
define('UPLOADS_DIR', STORAGE_DIR . '/uploads');
define('DB_FILE', STORAGE_DIR . '/panel.sqlite');
define('INSTALL_LOCK', STORAGE_DIR . '/installed.lock');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once APP_DIR . '/core/helpers.php';
ensure_storage_dirs();
require_once APP_DIR . '/core/view.php';
require_once APP_DIR . '/services/installer.php';

if (file_exists(DB_FILE)) {
    require_once APP_DIR . '/core/database.php';
    require_once APP_DIR . '/repositories/settings.php';
    require_once APP_DIR . '/repositories/users.php';
    require_once APP_DIR . '/repositories/roles.php';
    require_once APP_DIR . '/repositories/permissions.php';
    require_once APP_DIR . '/services/auth.php';
    require_once APP_DIR . '/services/permission.php';
    require_once APP_DIR . '/services/pdnsapi.php';
}
