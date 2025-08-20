<?php

define('ROOT_PATH', dirname(__DIR__));
define('BASE_URL', '/finals-main');

$routes = [
    // Auth routes
    '/' => '/auth/login.php',
    '/login' => '/auth/login.php',
    '/logout' => '/auth/logout.php',
    
    // User panel routes
    '/user' => '/dashboard/user_panel/user.php',
    '/user/create' => '/dashboard/user_panel/create.php',
    '/user/edit' => '/dashboard/user_panel/edit.php',
    '/user/import' => '/dashboard/user_panel/import.php',
    '/user/export' => '/dashboard/user_panel/export.php',
    
    // Admin panel routes
    '/admin' => '/dashboard/admin_panel/admin.php',
    '/admin/manage' => '/dashboard/admin_panel/manage.php'
];

function url($path) {
    return BASE_URL . $path;
}