<?php
$session = session();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Admin') ?> - Portal</title>
    <link rel="stylesheet" href="<?= base_url('assets/portal/css/bootstrap.min.css') ?>">
    <style>
        body { background-color: #f5f5f5; }
        .admin-navbar { background-color: #212529; }
        .admin-card { border: 0; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
    </style>
</head>
<body>
    <?php if ($session->get('admin_id')): ?>
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= base_url('admin') ?>">Portal Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('admin') ?>">Dasbor</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Pengaturan
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="settingsDropdown">
                            <li><a class="dropdown-item" href="<?= base_url('admin/settings/identity') ?>">Identitas Situs</a></li>
                            <li><a class="dropdown-item" href="<?= base_url('admin/settings/hero') ?>">Hero</a></li>
                            <li><a class="dropdown-item" href="<?= base_url('admin/settings/about') ?>">About</a></li>
                            <li><a class="dropdown-item" href="<?= base_url('admin/settings/services') ?>">Layanan</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('admin/change-password') ?>">Ubah Kata Sandi</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="navbar-text text-white me-3">Hai, <?= esc($session->get('admin_name') ?? 'Admin') ?></span>
                    </li>
                    <li class="nav-item">
                        <form method="post" action="<?= base_url('admin/logout') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-light" type="submit">Keluar</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container-fluid py-4">
        <?php if ($session->has('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= esc($session->getFlashdata('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>
        <?php if ($session->has('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= esc($session->getFlashdata('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </div>

    <script src="<?= base_url('assets/portal/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
