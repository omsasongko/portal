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
    <link rel="stylesheet" href="<?= base_url('assets/portal/style.css') ?>?v=2026110706">
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

    <div class="blog-list-wrapper" id="blog-root" data-results-url="<?= esc($resultsUrl) ?>" data-timeout="8000">
        <div class="container">
            <div class="section-title text-start">
                <h3><?= esc($blogTitle ?? 'Blog') ?></h3>
            </div>

            <div class="row">
                <?php // ~75% articles / 25% sidebar on wide screens; both stack on small screens. ?>
                <div class="col-lg-9 blog-main-col">
                    <div class="blog-loading" id="blog-loading" aria-hidden="true"><div class="blog-loading-box"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memuat hasil&hellip;</div></div>
                    <div class="blog-error" id="blog-error" hidden role="alert"><span>Gagal memuat hasil. Periksa koneksi Anda.</span><button type="button" class="blog-error-retry" id="blog-error-retry">Coba lagi</button></div>
                    <div id="blog-results-region"><?php
                        // Chips + cards/empty + pagination share one partial with
                        // the /blog/results JSON endpoint (identical markup).
                        echo view('portal/blog_results', [
                            'articles'           => $articles,
                            'total'              => $total,
                            'page'               => $page,
                            'totalPages'         => $totalPages,
                            'q'                  => $q,
                            'categorySlugs'      => $categorySlugs,
                            'tagSlugs'           => $tagSlugs,
                            'hasFilters'         => $hasFilters,
                            'selectedCategories' => $selectedCategories,
                            'selectedTags'       => $selectedTags,
                            'catNames'           => $catNames,
                            'articleCategoryIds' => $articleCategoryIds,
                            'cardLayout'         => $cardLayout,
                            'blogUrl'            => $blogUrl,
                        ]);
                    ?></div>
                </div>

                <?php // ----- Functional sidebar: Pencarian, Kategori, Tag (multi-select) ----- ?>
                <aside class="col-lg-3">
                    <form id="blog-filter-form" method="get" action="<?= base_url('blog') ?>" class="blog-sidebar" aria-label="Filter artikel">
                        <div class="bs-widget">
                            <h5>Pencarian</h5>
                            <div class="input-group blog-search">
                                <input type="search" class="form-control" placeholder="Cari artikel&hellip;" aria-label="Cari artikel" name="q" value="<?= esc($q) ?>">
                                <button class="btn blog-search-btn" type="submit">Cari</button>
                            </div>
                        </div>

                        <div class="bs-widget">
                            <h5>Kategori</h5>
                            <?php // ANY selected category matches; empty selection = no restriction. ?>
                            <div class="bs-check-list" role="group" aria-label="Filter kategori">
                                <?php foreach ($sidebarCategories as $sc): ?>
                                    <?php $checked = in_array($sc['slug'], $categorySlugs, true); ?>
                                <label class="form-check<?= $checked ? ' checked' : '' ?>">
                                    <input class="form-check-input bs-auto" type="checkbox" name="categories[]" value="<?= esc($sc['slug']) ?>" <?= $checked ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= esc($sc['name']) ?></span>
                                    <span class="bs-count"><?= (int) $sc['n'] ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="bs-widget">
                            <h5>Tag</h5>
                            <?php // ANY selected tag matches; combined with categories and search using AND. ?>
                            <div class="bs-check-list" role="group" aria-label="Filter tag">
                                <?php foreach ($sidebarTags as $st): ?>
                                    <?php $checked = in_array($st['slug'], $tagSlugs, true); ?>
                                <label class="form-check<?= $checked ? ' checked' : '' ?>">
                                    <input class="form-check-input bs-auto" type="checkbox" name="tags[]" value="<?= esc($st['slug']) ?>" <?= $checked ? 'checked' : '' ?>>
                                    <span class="form-check-label"><?= esc($st['name']) ?></span>
                                    <span class="bs-count"><?= (int) $st['n'] ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <a class="af-reset bs-reset" data-blog-link href="<?= base_url('blog') ?>">Reset filter</a>
                    </form>
                </aside>
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

    <script>
    (function () {
        "use strict";

        // Async filter updates: checkbox changes are debounced (250 ms) and
        // fetched as a small JSON payload from /blog/results — NOT full-page
        // navigations. Only the newest request may touch the DOM (sequence
        // guard) and superseded requests are aborted (AbortController).
        // Listeners are delegated and attached ONCE at startup, so replacing
        // the results region content never duplicates handlers. The sidebar
        // is never disabled; the overlay only covers the results column.
        var root = document.getElementById('blog-root');
        var form = document.getElementById('blog-filter-form');
        var region = document.getElementById('blog-results-region');
        var loading = document.getElementById('blog-loading');
        var errorBox = document.getElementById('blog-error');
        var retryBtn = document.getElementById('blog-error-retry');
        if (!root || !form || !region) return;

        var RESULTS_URL = root.getAttribute('data-results-url');
        var DEBOUNCE_MS = 250;

        var seq = 0;           // identifies the newest request
        var controller = null; // superseded requests are aborted
        var timer = null;
        var lastFetch = null;  // rebuilt on retry after a failure

        function timeoutMs() {
            // Read per request so data-timeout stays overridable at runtime
            // (used by tests to exercise the timeout path quickly).
            var v = parseInt(root.getAttribute('data-timeout') || '8000', 10);
            return v > 0 ? v : 8000;
        }

        function uniqueSorted(list) {
            var seen = {};
            var out = [];
            list.forEach(function (v) {
                v = String(v || '').trim();
                if (v !== '' && !seen[v]) { seen[v] = 1; out.push(v); }
            });
            return out.sort();
        }

        function selectionFromControls() {
            var picked = function (name) {
                return Array.prototype.slice.call(form.querySelectorAll('input[name="' + name + '"]:checked'))
                    .map(function (cb) { return cb.value; });
            };
            var qInput = form.querySelector('input[name="q"]');
            return {
                categories: picked('categories[]'),
                tags: picked('tags[]'),
                q: qInput ? qInput.value : ''
            };
        }

        function selectionFromParams(params) {
            return {
                categories: (params.get('categories') || '').split(','),
                tags: (params.get('tags') || '').split(','),
                q: params.get('q') || ''
            };
        }

        // Mirrors the server-side canonical builder: dedup + sort, q only
        // when non-empty, page only when > 1.
        function canonicalQuery(sel, page) {
            var parts = [];
            var cats = uniqueSorted(sel.categories);
            var tags = uniqueSorted(sel.tags);
            var q = String(sel.q || '').trim();
            if (cats.length) parts.push('categories=' + cats.map(encodeURIComponent).join(','));
            if (tags.length) parts.push('tags=' + tags.map(encodeURIComponent).join(','));
            if (q !== '') parts.push('q=' + encodeURIComponent(q));
            page = parseInt(page || '1', 10);
            if (page > 1) parts.push('page=' + page);
            return parts.join('&');
        }

        function syncControls(sel) {
            form.querySelectorAll('.bs-check-list input[type="checkbox"]').forEach(function (cb) {
                var list = cb.name === 'categories[]' ? sel.categories : sel.tags;
                cb.checked = list.indexOf(cb.value) !== -1;
            });
            var qInput = form.querySelector('input[name="q"]');
            if (qInput) qInput.value = sel.q || '';
        }

        function showLoading() { if (loading) loading.classList.add('show'); }
        function hideLoading() {
            if (loading) loading.classList.remove('show');
            form.classList.remove('is-loading');
        }
        function showError() { if (errorBox) errorBox.hidden = false; }
        function hideError() { if (errorBox) errorBox.hidden = true; }

        function apply(sel, page, push) {
            seq += 1;
            var my = seq;

            var query = canonicalQuery(sel, page);
            lastFetch = { sel: sel, page: page, push: push };

            if (controller) controller.abort(); // superseded request cancelled
            controller = new AbortController();
            var signal = controller.signal;

            var timedOut = false;
            var kill = setTimeout(function () {
                timedOut = true;
                controller.abort();
            }, timeoutMs());

            syncControls(sel);
            showLoading();
            hideError();

            fetch(RESULTS_URL + '?' + query, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: signal
            })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                if (my !== seq) return; // a newer request owns the UI now
                region.innerHTML = data.html;
                if (push) window.history.pushState({ blogFilters: true }, '', data.canonicalUrl);
                hideError();
            })
            .catch(function (err) {
                if (my !== seq) return;                    // superseded: never touch the UI
                if (err && err.name === 'AbortError' && !timedOut) return; // cancelled, not a failure
                showError();                                // network failure or timeout
            })
            .finally(function () {
                clearTimeout(kill);
                if (my === seq) hideLoading(); // an older request must NOT hide a newer one's loading state
            });
        }

        function schedule() {
            if (timer) clearTimeout(timer);
            timer = setTimeout(function () {
                timer = null;
                apply(selectionFromControls(), 1, true); // filter change → page 1
            }, DEBOUNCE_MS);
        }

        if (retryBtn) {
            retryBtn.addEventListener('click', function () {
                if (lastFetch) {
                    apply(lastFetch.sel, lastFetch.page, lastFetch.push);
                }
            });
        }

        // Delegated listeners — attached ONCE; replacing the region content
        // never duplicates them.
        form.addEventListener('change', function (e) {
            if (e.target && e.target.matches && e.target.matches('.bs-check-list input[type="checkbox"]')) {
                showLoading();
                schedule();
            }
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault(); // no full-page navigation for search either
            apply(selectionFromControls(), 1, true);
        });

        document.addEventListener('click', function (e) {
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var link = e.target.closest ? e.target.closest('a[data-blog-link]') : null;
            if (!link) return;
            e.preventDefault();
            var url = new URL(link.href);
            var params = url.searchParams;
            apply(selectionFromParams(params), parseInt(params.get('page') || '1', 10), true);
        });

        window.addEventListener('popstate', function () {
            var params = new URL(window.location.href).searchParams;
            apply(selectionFromParams(params), parseInt(params.get('page') || '1', 10), false);
        });

        // Restore a clean state when returning here via Back/Forward cache.
        window.addEventListener('pageshow', hideLoading);
    })();
    </script>

</body>
</html>
