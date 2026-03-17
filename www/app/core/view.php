<?php
function render(string $view, array $vars = [], string $layout = 'app'): void {
    extract($vars, EXTR_SKIP);
    $viewFile = APP_DIR . '/views/' . $view . '.php';
    $layoutFile = APP_DIR . '/views/layout/' . $layout . '.php';
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    require $layoutFile;
}
