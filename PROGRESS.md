# Portal — Progress Log

## Last Updated
2026-11-01

## Completed

### CMS Foundation
- [x] Added `.env` (ignored) and `.env.example` with app and database configuration
- [x] Connected to MariaDB 11.4 on `127.0.0.1:3307`; created `portal_db`
- [x] Created and ran migrations for `admin_users` and `admin_login_attempts`
- [x] Created `AdminModel`, `AdminLoginAttemptModel`, `AdminAuth` filter, and admin controllers (`Admin/Auth`, `Admin/Dashboard`)
- [x] Implemented routes: `/admin/login`, protected `/admin`, `POST /admin/logout`, and authenticated `/admin/change-password`
- [x] Enabled global CSRF protection and registered `adminAuth` filter
- [x] Set `indexPage` to empty string for clean URLs
- [x] Created admin views with local Bootstrap 5 assets and Indonesian labels
- [x] Created `admin:create` CLI command with hidden password input
- [x] Created initial admin user via CLI

### Site Identity, Header & Hero Settings (Owner Accepted)
- [x] Added additive migrations for `media`, `site_settings`, and `homepage_sections`
- [x] Seeded initial site identity (title, name, logo, favicon, apple-touch-icon, meta tags) and hero content from the existing homepage
- [x] Created `SettingModel`, `MediaModel`, and `HomepageSectionModel`
- [x] Created protected admin forms at `/admin/settings/identity` and `/admin/settings/hero` with Indonesian labels, server-side validation, and CSRF
- [x] Implemented validated image upload/selection for logo, favicon, apple-touch-icon, and hero background; rejected non-image/executable uploads
- [x] Rendered saved settings on the public homepage with output escaping; preserved Bootstrap 5 and responsive behavior
- [x] Updated dashboard to show real counts for implemented settings/hero modules and disabled links for unavailable modules

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

### Page Structure & CMS Planning
- [x] Inspected `app/Views/portal/home.php` and `page-template/index.html`
- [x] Mapped global layout components (head, loader, navigation, footer, scroll-to-top)
- [x] Mapped homepage sections (hero, about, services, portfolio, testimonials, blog, contact)
- [x] Documented editable fields, repeatable items, media references, and layout options
- [x] Proposed reuse of planned modules: Articles, Media, Menus, and Settings
- [x] Created `docs/PAGE_STRUCTURE.md` with component mapping, data relationships, implementation order, and unresolved decisions
- [x] Updated `PROGRESS.md`

## Verification Results
- [x] Homepage loads at `http://127.0.0.1:8081/` and public layout remains unchanged
- [x] Desktop layout matches supplied template
- [x] Navigation collapse toggles correctly via Bootstrap 5
- [x] Smooth scrolling to anchors works
- [x] Portfolio Isotope filters work (e.g. `.gal_a` shows 5 items)
- [x] Testimonials Owl Carousel renders slides and responds to `next.owl.carousel`
- [x] Browser console shows zero JavaScript errors and zero warnings
- [x] Anonymous access to `/admin` redirects to `/admin/login`
- [x] Invalid login returns to `/admin/login` with `Username atau kata sandi salah`
- [x] Valid login redirects to `/admin` dashboard
- [x] `POST /admin/logout` clears session and returns to login page
- [x] Authenticated password change works and requires the old password
- [x] CSRF tokens protect all forms; missing/mismatched tokens return 403
- [x] Admin can save/update site identity and hero settings with validation feedback
- [x] Invalid image uploads are rejected
- [x] Saved settings persist after reload and render on the public homepage
- [x] Public homepage preserves original appearance when settings are reset to defaults
- [x] Anonymous access to `/admin/settings/*` is denied and redirected to login

## Known Issues / Console Errors
- One external resource fails in the offline sandbox: `https://fonts.googleapis.com/css?family=Roboto+Mono:300,400,500,700` (imported by `style.css`). This is a network limitation, not a code error.

## Recent Fixes
- Fixed `AdminLoginAttemptModel` to disable automatic timestamps, resolving an `Unknown column 'updated_at' in 'INSERT INTO'` error on failed login attempts.
- Fixed `MediaModel` to disable automatic timestamps (media table only tracks `created_at`).

## Manual Acceptance Steps
1. Login at `/admin/login` and open `/admin`.
2. Click **Pengaturan > Identitas Situs**: update site title/name, meta tags, and upload/select logo/favicon/apple-touch-icon; save and verify public homepage reflects changes.
3. Click **Pengaturan > Hero**: update heading, description, rotating words, background image, and optional button; save and verify public homepage reflects changes.
4. Test validation: leave required fields blank and confirm error messages.
5. Test upload rejection: attempt to upload a `.php.jpg` file and confirm it is rejected.
6. Resize browser to mobile width or use device emulation: confirm layout remains responsive and navigation collapses.

## Unverified
- [ ] Mobile viewport rendering (browser automation tools in this session do not support viewport resizing)
- [ ] Physical touch interactions on real mobile devices

## Pending (future tasks)
- [x] Database setup and migrations
- [x] Admin authentication and dashboard
- [x] Site identity and hero settings
- [ ] Category/tag CRUD
- [ ] Media library
- [ ] Article editor with draft/preview/publish
- [ ] Public article listing/detail/search pages
- [ ] Menus and site settings
- [ ] Backup/restore documentation
