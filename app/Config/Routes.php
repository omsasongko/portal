<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');
$routes->get('pages/(:segment)', 'Page::show/$1');
$routes->get('blog', 'Blog::index');
$routes->get('blog/(:segment)', 'Blog::show/$1');

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
    $routes->get('settings/portfolio', 'Admin\Settings::portfolio');
    $routes->post('settings/portfolio', 'Admin\Settings::doPortfolio');
    $routes->post('settings/portfolio/category', 'Admin\Settings::portfolioCategorySave');
    $routes->post('settings/portfolio/category/delete', 'Admin\Settings::portfolioCategoryDelete');
    $routes->post('settings/portfolio/item', 'Admin\Settings::portfolioItemSave');
    $routes->post('settings/portfolio/item/delete', 'Admin\Settings::portfolioItemDelete');
    $routes->post('settings/portfolio/item/reorder', 'Admin\Settings::portfolioItemReorder');
    $routes->get('settings/testimonials', 'Admin\Settings::testimonials');
    $routes->post('settings/testimonials', 'Admin\Settings::doTestimonials');
    $routes->post('settings/testimonials/card', 'Admin\Settings::testimonialSave');
    $routes->post('settings/testimonials/card/delete', 'Admin\Settings::testimonialDelete');
    $routes->post('settings/testimonials/reorder', 'Admin\Settings::testimonialReorder');
    $routes->get('settings/blog', 'Admin\Settings::blog');
    $routes->post('settings/blog', 'Admin\Settings::doBlog');

    // Article taxonomy (categories and tags)
    $routes->get('categories', 'Admin\Categories::index');
    $routes->post('categories/save', 'Admin\Categories::save');
    $routes->post('categories/delete', 'Admin\Categories::delete');
    $routes->get('tags', 'Admin\Tags::index');
    $routes->post('tags/save', 'Admin\Tags::save');
    $routes->post('tags/delete', 'Admin\Tags::delete');

    // Pages
    $routes->get('pages', 'Admin\Pages::index');
    $routes->get('pages/create', 'Admin\Pages::create');
    $routes->get('pages/edit/(:num)', 'Admin\Pages::edit/$1');
    $routes->post('pages/save', 'Admin\Pages::save');
    $routes->post('pages/delete', 'Admin\Pages::delete');

    // Menu
    $routes->get('menu', 'Admin\Menu::index');
    $routes->post('menu/save', 'Admin\Menu::save');
    $routes->post('menu/delete', 'Admin\Menu::delete');
    $routes->post('menu/move-up', 'Admin\Menu::moveUp');
    $routes->post('menu/move-down', 'Admin\Menu::moveDown');
    $routes->post('menu/toggle-visibility', 'Admin\Menu::toggleVisibility');

    // Articles
    $routes->get('articles', 'Admin\Articles::index');
    $routes->get('articles/create', 'Admin\Articles::create');
    $routes->get('articles/edit/(:num)', 'Admin\Articles::edit/$1');
    $routes->get('articles/preview/(:num)', 'Admin\Articles::preview/$1');
    $routes->post('articles/save', 'Admin\Articles::save');
    $routes->post('articles/delete', 'Admin\Articles::delete');
    $routes->post('articles/upload-image', 'Admin\Articles::uploadImage');
    $routes->post('articles/crop-image', 'Admin\Articles::cropImage');
    $routes->get('articles/media-library', 'Admin\Articles::mediaLibrary');
    $routes->post('articles/embed-youtube', 'Admin\Articles::embedYoutube');
});
