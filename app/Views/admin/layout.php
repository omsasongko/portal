<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Admin') ?> | <?= esc(($identity['site_name'] ?? '') ?: 'Portal Admin') ?></title>

    <link rel="icon" href="<?= base_url($identity['favicon'] ?? 'assets/portal/images/favicon.ico') ?>?v=2026110707" type="image/x-icon">
    <link rel="shortcut icon" href="<?= base_url($identity['favicon'] ?? 'assets/portal/images/favicon.ico') ?>?v=2026110707" type="image/x-icon">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.3.0/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.16.0/styles/overlayscrollbars.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/admin/css/adminlte.min.css') ?>">

    <script>
        const storedTheme = localStorage.getItem('theme');
        const getPreferredTheme = () => {
            if (storedTheme) return storedTheme;
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        };
        document.documentElement.setAttribute('data-bs-theme', getPreferredTheme());
    </script>
</head>

<?php
$isLoggedIn = session()->get('admin_id');
$currentUrl = current_url();
$uri = service('uri');
$segment2 = $uri->getTotalSegments() >= 2 ? $uri->getSegment(2) : '';
$segment3 = $uri->getTotalSegments() >= 3 ? $uri->getSegment(3) : '';
?>

<?php if ($isLoggedIn): ?>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">

        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item d-flex align-items-center me-3">
                        <span class="nav-link">Hai, <?= esc(session()->get('admin_name')) ?></span>
                    </li>
                    <li class="nav-item">
                        <form action="<?= base_url('admin/logout') ?>" method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="<?= base_url('admin') ?>" class="brand-link">
                    <img src="<?= base_url($identity['favicon'] ?? 'assets/portal/images/favicon.ico') ?>?v=2026110707" alt="<?= esc(($identity['site_name'] ?? '') ?: 'Portal Admin') ?>" class="brand-image opacity-75 shadow">
                    <span class="brand-text fw-light"><?= esc(($identity['site_name'] ?? '') ?: 'Portal Admin') ?></span>
                </a>
            </div>

            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                        <li class="nav-item">
                            <a href="<?= base_url('admin') ?>" class="nav-link <?= ($segment2 === '' || $segment2 === false) && str_ends_with($currentUrl, '/admin') ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-speedometer"></i>
                                <p>Dasbor</p>
                            </a>
                        </li>

                        <?php
                        $settingsChildren = ['identity', 'hero', 'about', 'services', 'portfolio', 'testimonials', 'blog', 'contact'];
                        $isSettingsOpen = ($segment2 === 'settings');
                        ?>
                        <li class="nav-item <?= $isSettingsOpen ? 'menu-open' : '' ?>">
                            <a href="#" class="nav-link <?= $isSettingsOpen ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-gear"></i>
                                <p>
                                    Pengaturan
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/settings/identity') ?>" class="nav-link <?= $segment2 === 'settings' && $segment3 === 'identity' ? 'active' : '' ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Identitas Situs</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/settings/hero') ?>" class="nav-link <?= $segment2 === 'settings' && $segment3 === 'hero' ? 'active' : '' ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Hero</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/settings/about') ?>" class="nav-link <?= $segment2 === 'settings' && $segment3 === 'about' ? 'active' : '' ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>About</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/settings/services') ?>" class="nav-link <?= $segment2 === 'settings' && $segment3 === 'services' ? 'active' : '' ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Layanan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/settings/portfolio') ?>" class="nav-link <?= $segment2 === 'settings' && $segment3 === 'portfolio' ? 'active' : '' ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Portofolio</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/settings/testimonials') ?>" class="nav-link <?= $segment2 === 'settings' && $segment3 === 'testimonials' ? 'active' : '' ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Testimoni</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/settings/blog') ?>" class="nav-link <?= $segment2 === 'settings' && $segment3 === 'blog' ? 'active' : '' ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Blog</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?= base_url('admin/settings/contact') ?>" class="nav-link <?= $segment2 === 'settings' && $segment3 === 'contact' ? 'active' : '' ?>">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Contact</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a href="<?= base_url('admin/categories') ?>" class="nav-link <?= $segment2 === 'categories' ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-folder"></i>
                                <p>Kategori</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= base_url('admin/tags') ?>" class="nav-link <?= $segment2 === 'tags' ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-tags"></i>
                                <p>Tag</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= base_url('admin/articles') ?>" class="nav-link <?= $segment2 === 'articles' ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-newspaper"></i>
                                <p>Artikel</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= base_url('admin/pages') ?>" class="nav-link <?= $segment2 === 'pages' ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-file-earmark-text"></i>
                                <p>Halaman</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= base_url('admin/menu') ?>" class="nav-link <?= $segment2 === 'menu' ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-menu-button-wide"></i>
                                <p>Menu</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= base_url('admin/analytics') ?>" class="nav-link <?= $segment2 === 'analytics' ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-bar-chart"></i>
                                <p>Analitik</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= base_url('admin/messages') ?>" class="nav-link <?= $segment2 === 'messages' ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-envelope"></i>
                                <p>Pesan<?php
                                    $__unread = (new \App\Models\ContactMessageModel())->countUnread();
                                    if ($__unread): ?> <span class="badge text-bg-danger ms-1"><?= $__unread ?></span><?php endif;
                                ?></p>
                            </a>
                        </li>

                        <li class="nav-header">-</li>

                        <li class="nav-item">
                            <a href="<?= base_url('admin/change-password') ?>" class="nav-link <?= $segment2 === 'change-password' ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-key"></i>
                                <p>Ubah Kata Sandi</p>
                            </a>
                        </li>

                    </ul>
                </nav>
            </div>
        </aside>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0"><?= esc($title ?? 'Dasbor') ?></h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="<?= base_url('admin') ?>">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?= esc($title ?? 'Dasbor') ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= esc(session()->getFlashdata('success')) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= esc(session()->getFlashdata('error')) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?= $this->renderSection('content') ?>
                </div>
            </div>
        </main>

        <footer class="app-footer">
            <div class="float-end d-none d-sm-inline"><?= esc($identity['site_name'] ?? 'Portal Admin') ?></div>
            <strong><?= esc($identity['site_name'] ?? 'Portal Admin') ?></strong>
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.16.0/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
    <script src="<?= base_url('assets/admin/js/adminlte.min.js') ?>"></script>

    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = { scrollbarTheme: 'os-theme-light', scrollbarAutoHide: 'leave', scrollbarClickScroll: true };
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: { theme: Default.scrollbarTheme, autoHide: Default.scrollbarAutoHide, clickScroll: Default.scrollbarClickScroll }
                });
            }
        });
    </script>

    <?= $this->renderSection('scripts') ?>
</body>

<?php else: ?>

<body class="bg-body-tertiary">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>

    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.16.0/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
    <script src="<?= base_url('assets/admin/js/adminlte.min.js') ?>"></script>

    <?= $this->renderSection('scripts') ?>
</body>

<?php endif; ?>

</html>
