# Portal — Progress Log

## Last Updated
2026-11-01

## Completed

### Environment / Infrastructure
- [x] Apache 2.4.69 configured for Portal on `127.0.0.1:8081`
- [x] DocumentRoot points to `/Volumes/Data/Project/portal/public`
- [x] PHP-FPM 8.5.12 connected via `mod_proxy_fcgi`
- [x] Obsolete `personal-page/docs/apache/bertahanhidup.conf` include removed
- [x] Apache validated (`httpd -t`) and restarted

### Public Homepage
- [x] Inspected `page-template/index.html` and its assets (CSS, JS, fonts, images, uploads)
- [x] Copied runtime assets to `public/assets/portal/` (css, js, images, uploads, fonts, style.css)
- [x] Created CodeIgniter view `app/Views/portal/home.php`
- [x] Updated asset URLs to use `base_url()` helper
- [x] Updated `Home::index()` to render `portal/home`
- [x] Updated `app/Config/App.php` baseURL to `http://127.0.0.1:8081/`
- [x] Verified homepage loads at `http://127.0.0.1:8081/`
- [x] Verified key assets return HTTP 200
- [x] Desktop browser screenshot captured; layout matches supplied template

### Homepage JS Stabilization
- [x] Removed fake `window.exports` guard
- [x] Loaded `images-loded.min.js` before `custom.js` so Isotope/imagesLoaded works
- [x] Patched `public/assets/portal/js/all.js` to wrap the bundled Retina script in an IIFE, eliminating the `exports is not defined` error
- [x] Decomposed `all.js` into separate scripts and kept jQuery (v1.12.4 extracted from the old bundle)

### Bootstrap 5 Migration
- [x] Identified Bootstrap 4.1.1 in `js/all.js` and Bootstrap 4.1.0 in `css/bootstrap.min.css`
- [x] Replaced runtime Bootstrap CSS/JS with supplied local Bootstrap 5.3.8 assets
- [x] Stopped loading the old `js/all.js` bundle (jQuery/Bootstrap 4/plugins bundle)
- [x] Updated `app/Views/portal/home.php` markup: `data-toggle`→`data-bs-toggle`, `data-target`→`data-bs-target`, `ml-auto`→`ms-auto`, `text-left`→`text-start`, `img-rounded`→`rounded`, `hidden-xs`→`d-none d-sm-block`, `form-group`→`mb-3`, `help-block`→`form-text`
- [x] Updated `public/assets/portal/js/custom.js` to use Bootstrap 5 APIs: `new bootstrap.ScrollSpy(...)` and `bootstrap.Collapse.getOrCreateInstance(...).hide()`
- [x] Extracted template plugin initialization (WOW, prettyPhoto, Owl Carousel) from old `all.js` and added it to `custom.js`
- [x] Preserved `page-template/` source files unchanged

## Verification Results
- [x] Homepage loads at `http://127.0.0.1:8081/`
- [x] Desktop layout matches supplied template
- [x] Navigation collapse toggles correctly via Bootstrap 5
- [x] Smooth scrolling to anchors works
- [x] Portfolio Isotope filters work (e.g. `.gal_a` shows 5 items)
- [x] Testimonials Owl Carousel renders slides and responds to `next.owl.carousel`
- [x] Browser console shows zero JavaScript errors and zero warnings

## Known Issues / Console Errors
- One external resource fails in the offline sandbox: `https://fonts.googleapis.com/css?family=Roboto+Mono:300,400,500,700` (imported by `style.css`). This is a network limitation, not a code error.

## Unverified
- [ ] Mobile viewport rendering (browser automation tools in this session do not support viewport resizing)
- [ ] Physical touch interactions on real mobile devices

## Pending (future tasks)
- [ ] Database setup and migrations
- [ ] Admin authentication and dashboard
- [ ] Category/tag CRUD
- [ ] Media library
- [ ] Article editor with draft/preview/publish
- [ ] Public article listing/detail/search pages
- [ ] Menus and site settings
- [ ] Backup/restore documentation
