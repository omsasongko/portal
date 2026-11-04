<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Basic -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Mobile Metas -->
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">

     <!-- Site Metas -->
    <title>Blog | <?= esc($identity['site_title'] ?? 'Portal') ?></title>
    <meta name="keywords" content="<?= esc($identity['meta_keywords'] ?? '') ?>">
    <meta name="description" content="<?= esc($identity['meta_description'] ?? '') ?>">
    <meta name="author" content="<?= esc($identity['meta_author'] ?? '') ?>">

    <!-- Site Icons -->
    <link rel="shortcut icon" href="<?= base_url($identity['favicon'] ?? 'assets/portal/images/favicon.ico') ?>" type="image/x-icon" />
    <link rel="apple-touch-icon" href="<?= base_url($identity['apple_touch_icon'] ?? 'assets/portal/images/apple-touch-icon.png') ?>">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/css/bootstrap.min.css') ?>">
    <!-- Site CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/style.css') ?>">
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/css/responsive.css') ?>">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/css/custom.css') ?>">

    <style>
        .blog-list-wrapper {
            padding-top: 100px;
            padding-bottom: 60px;
            min-height: 60vh;
        }
        .blog-list-wrapper .post-box .post-thumb img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        .blog-list-wrapper .post-info h4 a {
            color: inherit;
        }
        .blog-list-wrapper .pagination {
            margin-top: 30px;
        }
    </style>
</head>
<body id="page-top" class="politics_version">

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
              <a class="nav-link" href="<?= esc($mi['url']) ?>"><?= esc($mi['label']) ?></a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </nav>

    <div class="blog-list-wrapper">
        <div class="container">
            <div class="section-title text-start">
                <h3>Blog</h3>
                <p>Semua artikel yang telah diterbitkan.</p>
            </div>

            <?php if (empty($articles)): ?>
                <p class="text-muted">Belum ada artikel yang diterbitkan.</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($articles as $a): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="post-box">
                            <?php if (! empty($a['cover_image'])): ?>
                            <div class="post-thumb">
                                <a href="<?= base_url('blog/' . $a['slug']) ?>">
                                    <img src="<?= base_url($a['cover_image']) ?>" class="img-fluid" alt="<?= esc($a['cover_alt'] ?? $a['title']) ?>" />
                                </a>
                            </div>
                            <?php endif; ?>
                            <div class="post-info">
                                <h4><a href="<?= base_url('blog/' . $a['slug']) ?>"><?= esc($a['title']) ?></a></h4>
                                <ul>
                                    <?php if (! empty($a['category_id']) && isset($catNames[$a['category_id']])): ?>
                                    <li><?= esc($catNames[$a['category_id']]) ?></li>
                                    <?php endif; ?>
                                    <?php if (! empty($a['published_at'])): ?>
                                    <li><?= esc(date('d M Y', strtotime($a['published_at']))) ?></li>
                                    <?php endif; ?>
                                </ul>
                                <?php if (! empty($a['excerpt'])): ?>
                                <p><?= esc($a['excerpt']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($pager->getPageCount() > 1): ?>
                <nav aria-label="Navigasi halaman artikel">
                    <?= $pager->only(['page'])->links() ?>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
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
                    <p class="footer-company-name">All Rights Reserved. &copy; <?= date('Y') ?> <a href="<?= base_url('/') ?>"><?= esc($identity['site_name'] ?? 'Portal') ?></a></p>
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
                bootstrap.Collapse.getOrCreateInstance(navbarCollapse).hide();
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
