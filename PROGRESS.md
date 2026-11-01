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
- [x] Verified zero console errors and zero console warnings
- [x] Verified navigation menu toggles on click
- [x] Verified portfolio filter buttons trigger Isotope without errors
- [x] Verified testimonials carousel renders with navigation controls
- [x] Confirmed Bootstrap 4.1.1 is the active public Bootstrap version

## Known Issues / Console Errors
- None. Browser console is clean (no errors, no warnings).

## Unverified
- [ ] Mobile viewport rendering (no browser tool support for viewport resizing in this session)
- [ ] Physical touch interactions on real mobile devices

## Pending (future tasks)
- [ ] Bootstrap 5 migration for admin/public redesign (deferred)
- [ ] Database setup and migrations
- [ ] Admin authentication and dashboard
- [ ] Category/tag CRUD
- [ ] Media library
- [ ] Article editor with draft/preview/publish
- [ ] Public article listing/detail/search pages
- [ ] Menus and site settings
- [ ] Backup/restore documentation
