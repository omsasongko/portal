<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Basic -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Mobile Metas -->
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">

     <!-- Site Metas -->
    <title><?= esc($page['title']) ?> | <?= esc($identity['site_title'] ?? 'Portal') ?></title>
    <meta name="keywords" content="<?= esc($identity['meta_keywords'] ?? '') ?>">
    <meta name="description" content="<?= esc($identity['meta_description'] ?? '') ?>">
    <meta name="author" content="<?= esc($identity['meta_author'] ?? '') ?>">

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
        /* Full-height flex layout so the footer stays at the bottom on short pages. */
        body#page-top.politics_version {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-orient: vertical;
            -webkit-box-direction: normal;
            -webkit-flex-direction: column;
            -ms-flex-direction: column;
            flex-direction: column;
            min-height: 100vh;
        }
        body#page-top.politics_version > main.page-content-wrapper {
            -webkit-box-flex: 1;
            -webkit-flex: 1 0 auto;
            -ms-flex: 1 0 auto;
            flex: 1 0 auto;
        }
        body#page-top.politics_version > footer.copyrights {
            -webkit-flex-shrink: 0;
            -ms-flex-negative: 0;
            flex-shrink: 0;
        }

        .page-content-wrapper {
            padding-top: 100px;
            padding-bottom: 60px;
        }
        .page-content-wrapper .section-title {
            margin-bottom: 60px;
        }
        .page-content-wrapper .section-title h3 {
            margin: 0 0 25px;
            padding: 0;
            text-transform: none;
        }

        @media (max-width: 767px) {
            body#page-top.politics_version {
                min-height: 100vh;
            }
            .page-content-wrapper .section-title h3 {
                font-size: 28px;
                line-height: 1.4;
            }
        }
        .page-content-wrapper img {
            max-width: 100%;
            height: auto;
        }
        .page-content-wrapper table {
            width: 100%;
            margin-bottom: 1rem;
        }
        .page-content-wrapper table th,
        .page-content-wrapper table td {
            padding: 0.5rem;
            border: 1px solid #dee2e6;
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
              <a class="nav-link<?= !empty($mi['active']) ? ' active' : '' ?>" href="<?= esc($mi['url']) ?>"><?= esc($mi['label']) ?></a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </nav>

    <main class="page-content-wrapper">
        <div class="container">
            <div class="section-title text-start">
                <h3><?= esc($page['title']) ?></h3>
            </div>
            <div class="page-body">
                <?= $page['content'] ?>
            </div>
        </div>
    </main>

    <footer class="copyrights">
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
    </footer><!-- end copyrights -->

    <?php if (($identity['show_scroll_to_top'] ?? '1') === '1'): ?>
    <a href="#" id="scroll-to-top" class="dmtop global-radius"><i class="fa fa-angle-up"></i></a>
    <?php endif; ?>

    <!-- CORE -->
    <script src="<?= base_url('assets/portal/js/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/bootstrap.bundle.min.js') ?>"></script>

    <script>
    (function($) {
        "use strict";

        // Collapse Navbar (always shrunk on standalone pages)
        $("#mainNav").addClass("navbar-shrink");

        // Close mobile menu on link click
        $('.nav-link').click(function () {
            var navbarCollapse = document.querySelector('.navbar-collapse');
            if (navbarCollapse) {
                bootstrap.Collapse.getOrCreateInstance(navbarCollapse, { toggle: false }).hide();
            }
        });

        // Scroll to top
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
