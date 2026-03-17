<?php
function handle_roles(): void {
    require_permission('roles.manage');
    render('roles/list', ['map' => roles_permissions_map()]);
}
