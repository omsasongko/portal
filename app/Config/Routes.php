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

    // Settings
    $routes->get('settings/identity', 'Admin\Settings::identity');
    $routes->post('settings/identity', 'Admin\Settings::doIdentity');
    $routes->get('settings/hero', 'Admin\Settings::hero');
    $routes->post('settings/hero', 'Admin\Settings::doHero');
    $routes->get('settings/about', 'Admin\Settings::about');
    $routes->post('settings/about', 'Admin\Settings::doAbout');
    $routes->get('settings/services', 'Admin\Settings::services');
    $routes->post('settings/services', 'Admin\Settings::doServices');
    $routes->post('settings/services/card', 'Admin\Settings::serviceCardSave');
    $routes->post('settings/services/card/delete', 'Admin\Settings::serviceCardDelete');
    $routes->post('settings/services/reorder', 'Admin\Settings::serviceCardReorder');
});
