<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');

// Admin auth routes
$routes->get('admin/login', 'Admin\Auth::loginForm');
$routes->post('admin/login', 'Admin\Auth::doLogin');
$routes->post('admin/logout', 'Admin\Auth::logout');

// Protected admin routes
$routes->group('admin', ['filter' => 'adminAuth'], function (RouteCollection $routes) {
    $routes->get('/', 'Admin\Dashboard::index');
    $routes->get('change-password', 'Admin\Auth::changePasswordForm');
    $routes->post('change-password', 'Admin\Auth::doChangePassword');
});
