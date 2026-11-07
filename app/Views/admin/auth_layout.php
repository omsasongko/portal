<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Login') ?> | <?= esc(($identity['site_name'] ?? '') ?: 'Portal Admin') ?></title>

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

<body class="login-page bg-body-secondary">
    <div class="login-box">
        <div class="login-logo">
            <a href="<?= base_url('admin') ?>">
                <?php if (! empty($identity['logo'])): ?>
                    <img src="<?= base_url($identity['logo']) ?>?v=2026110707" alt="<?= esc(($identity['site_name'] ?? '') ?: 'Portal Admin') ?>" style="max-height: 48px; width: auto;">
                <?php else: ?>
                    <b><?= esc(($identity['site_name'] ?? '') ?: 'Portal') ?></b> Admin
                <?php endif; ?>
            </a>
        </div>

        <div class="card">
            <div class="card-body login-card-body">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.16.0/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
    <script src="<?= base_url('assets/admin/js/adminlte.min.js') ?>"></script>

    <?= $this->renderSection('scripts') ?>
</body>

</html>
