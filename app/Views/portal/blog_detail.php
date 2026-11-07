<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Basic -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Mobile Metas -->
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">

     <!-- Site Metas -->
    <title><?= esc($article['title']) ?> | <?= esc($identity['site_title'] ?? 'Portal') ?></title>
    <meta name="keywords" content="<?= esc($identity['meta_keywords'] ?? '') ?>">
    <meta name="description" content="<?= esc($article['excerpt'] ?? $identity['meta_description'] ?? '') ?>">
    <meta name="author" content="<?= esc($identity['meta_author'] ?? '') ?>">
    <?php if ($article['status'] !== 'published'): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>

    <!-- Site Icons -->
    <link rel="shortcut icon" href="<?= base_url($identity['favicon'] ?? 'assets/portal/images/favicon.ico') ?>" type="image/x-icon" />
    <link rel="apple-touch-icon" href="<?= base_url($identity['apple_touch_icon'] ?? 'assets/portal/images/apple-touch-icon.png') ?>">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/css/bootstrap.min.css') ?>">
    <!-- Site CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/style.css') ?>?v=2026110706">
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/css/responsive.css') ?>">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/css/custom.css') ?>">

    <style>
        .article-wrapper {
            padding-top: 100px;
            padding-bottom: 60px;
            min-height: 60vh;
        }
        .article-wrapper h1 {
            margin-bottom: 10px;
        }
        .article-meta {
            color: #999;
            font-size: 14px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .article-meta .badge {
            text-transform: none;
        }
        .article-cover {
            margin-bottom: 30px;
        }
        .article-cover img {
            width: 100%;
            max-height: 420px;
            object-fit: cover;
            border-radius: 6px;
        }
        .article-body img {
            max-width: 100%;
            height: auto;
        }
        .article-body img.img-align-left {
            display: block;
            margin: 10px auto 10px 0;
        }
        .article-body img.img-align-center {
            display: block;
            margin: 10px auto;
        }
        .article-body img.img-align-right {
            display: block;
            margin: 10px 0 10px auto;
        }
        .article-body .video-embed-wrapper {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            max-width: 100%;
            margin: 10px 0;
        }
        .article-body .video-embed-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .article-body table {
            width: 100%;
            margin-bottom: 1rem;
        }
        .article-body table th,
        .article-body table td {
            padding: 0.5rem;
            border: 1px solid #dee2e6;
        }
        .article-tags {
            margin-top: 25px;
        }
        .related-sidebar .card {
            margin-bottom: 15px;
        }
        .related-sidebar .card img {
            height: 120px;
            object-fit: cover;
        }
        .demo-preview-banner {
            position: relative;
            z-index: 1050;
        }
    </style>
</head>
<body id="page-top" class="politics_version">

    <?php if (! empty($isPreview)): ?>
    <div class="demo-preview-banner alert alert-warning text-center mb-0 rounded-0">
        <strong>Pratinjau admin</strong> — artikel ini berstatus <em><?= $article['status'] === 'draft' ? 'Draf' : 'Terbit' ?></em> dan hanya terlihat oleh admin yang sedang masuk.
        <?php if (! empty($isUnsaved)): ?><br><strong>Pratinjau perubahan belum disimpan</strong> — perubahan pada form belum masuk ke database. Tekan "Simpan" pada form untuk menyimpannya.<?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-shrink" id="mainNav">
      <div class="container">
        <a class="navbar-brand" href="<?= base_url('/') ?>">
			<img class="img-fluid" src="<?= base_url($identity['logo'] ?? 'assets/portal/images/logo.png') ?>" alt="<?= esc($identity['site_name'] ?? '') ?>" />
		</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          Menu
          <i class="fa fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav text-uppercase ms-auto">
            <?php foreach ($menuItems as $mi): ?>
            <li class="nav-item">
              <a class="nav-link<?= !empty($mi['active']) ? ' active' : '' ?>" href="<?= esc($mi['url']) ?>"><?= esc($mi['label']) ?></a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </nav>

    <div class="article-wrapper">
        <div class="container">
            <div class="row">
                <!-- Reading area: full width or 8 columns with sidebar -->
                <div class="<?= $article['detail_layout'] === 'sidebar' ? 'col-lg-8' : 'col-12' ?>">
                    <h1><?= esc($article['title']) ?></h1>
                    <div class="article-meta">
                        <?php if (! empty($article['published_at'])): ?>
                            <span><?= esc(date('d M Y', strtotime($article['published_at']))) ?></span>
                        <?php endif; ?>
                        <?php foreach (($categories ?? []) as $c): ?>
                            &middot; <span class="badge text-bg-secondary"><?= esc($c['name']) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <?php if (! empty($article['cover_image'])): ?>
                    <div class="article-cover">
                        <img src="<?= base_url($article['cover_image']) ?>" alt="<?= esc($article['cover_alt'] ?? $article['title']) ?>" class="img-fluid" />
                    </div>
                    <?php endif; ?>

                    <div class="article-body">
                        <?= $article['body'] ?>
                    </div>

                    <?php if (! empty($tags)): ?>
                    <div class="article-tags">
                        <?php foreach ($tags as $tag): ?>
                            <span class="badge text-bg-light border">#<?= esc($tag['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="<?= base_url('blog') ?>" class="sim-btn btn-hover-new" data-text="Kembali ke Blog"><span>&larr; Kembali ke Blog</span></a>
                    </div>
                </div>

                <?php if ($article['detail_layout'] === 'sidebar'): ?>
                <!-- Related articles sidebar -->
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="related-sidebar">
                        <h4 class="mb-3">Artikel Terkait</h4>
                        <?php if (empty($related)): ?>
                            <p class="text-muted">Belum ada artikel terkait.</p>
                        <?php else: ?>
                            <?php foreach ($related as $rel): ?>
                            <div class="card">
                                <?php if (! empty($rel['cover_image'])): ?>
                                    <img src="<?= base_url($rel['cover_image']) ?>" class="card-img-top" alt="<?= esc($rel['cover_alt'] ?? $rel['title']) ?>">
                                <?php endif; ?>
                                <div class="card-body py-2">
                                    <h6 class="card-title mb-1"><a href="<?= base_url('blog/' . $rel['slug']) ?>"><?= esc($rel['title']) ?></a></h6>
                                    <?php if (! empty($rel['published_at'])): ?>
                                        <small class="text-muted"><?= esc(date('d M Y', strtotime($rel['published_at']))) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="copyrights">
        <div class="container">
            <div class="footer-distributed">
                <div class="footer-left">
                    <p class="footer-links">
                        <?php foreach ($menuItems as $mi): ?>
                            <a href="<?= esc($mi['url']) ?>"><?= esc($mi['label']) ?></a>
                        <?php endforeach; ?>
                    </p>
                    <p class="footer-company-name">&copy; <?= date('Y') ?> <a href="<?= base_url('/') ?>"><?= esc($identity['site_name'] ?? 'Pak De Sasongko') ?></a></p>
                </div>
            </div>
        </div><!-- end container -->
    </div><!-- end copyrights -->

    <?php if (($identity['show_scroll_to_top'] ?? '1') === '1'): ?>
    <a href="#" id="scroll-to-top" class="dmtop global-radius"><i class="fa fa-angle-up"></i></a>
    <?php endif; ?>

    <!-- CORE -->
    <script src="<?= base_url('assets/portal/js/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/bootstrap.bundle.min.js') ?>"></script>

    <script>
    (function($) {
        "use strict";

        $("#mainNav").addClass("navbar-shrink");

        $('.nav-link').click(function () {
            var navbarCollapse = document.querySelector('.navbar-collapse');
            if (navbarCollapse) {
                bootstrap.Collapse.getOrCreateInstance(navbarCollapse, { toggle: false }).hide();
            }
        });

        if ($('#scroll-to-top').length) {
            var scrollTrigger = 100;
            var backToTop = function () {
                if ($(window).scrollTop() > scrollTrigger) {
                    $('#scroll-to-top').addClass('show');
                } else {
                    $('#scroll-to-top').removeClass('show');
                }
            };
            backToTop();
            $(window).on('scroll', backToTop);
            $('#scroll-to-top').on('click', function (e) {
                e.preventDefault();
                $('html, body').scrollTop(0);
            });
        }
    })(jQuery);
    </script>

</body>
</html>
