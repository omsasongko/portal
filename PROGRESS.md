# Portal — Progress Log

## Last Updated
2026-11-02

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

### About Section (Ready for Owner Review)
- [x] Added additive migration to seed `about` homepage section from existing content
- [x] Reused `homepage_sections` storage and media upload/selection for About
- [x] Added protected admin form at `/admin/settings/about` with Indonesian labels, validation, and CSRF
- [x] Supported heading, body, image, image alt text, button label/link, layout (right/left/text-only), and visibility toggle
- [x] Rendered saved About content on public homepage while preserving `#about` section, Bootstrap 5 layout, and interactions
- [x] Hid About navigation anchor when section is disabled
- [x] Verified saving, persistence, invalid input, anonymous access denial, and homepage rendering
- [x] Seeded initial site identity (title, name, logo, favicon, apple-touch-icon, meta tags) and hero content from the existing homepage
- [x] Created `SettingModel`, `MediaModel`, and `HomepageSectionModel`
- [x] Created protected admin forms at `/admin/settings/identity` and `/admin/settings/hero` with Indonesian labels, server-side validation, and CSRF
- [x] Implemented validated image upload/selection for logo, favicon, apple-touch-icon, and hero background; rejected non-image/executable uploads
- [x] Rendered saved settings on the public homepage with output escaping; preserved Bootstrap 5 and responsive behavior
- [x] Updated dashboard to show real counts for implemented settings/hero modules and disabled links for unavailable modules

### Services Section (Owner Accepted)
- [x] Added additive migration creating `homepage_section_items` table and seeding the `services` section + 6 default cards from existing content
- [x] Added `HomepageSectionItemModel` for service card storage
- [x] Added protected admin screen at `/admin/settings/services` with Indonesian labels, validation, and CSRF
- [x] Supported section title, subtitle, layout (2/3 columns), and visibility toggle
- [x] Supported service card add/edit/delete/ordering via a predefined icon allowlist (no arbitrary HTML)
- [x] Rendered saved Services content on public homepage while preserving `#services` section, Bootstrap 5 layout, and interactions
- [x] Hid Services navigation anchor when section is disabled
- [x] Verified CRUD, ordering, persistence, validation, anonymous access denial, and public rendering

### Portfolio / Gallery Section (Owner reported functionality working)
- [x] Added additive migration creating `portfolio_categories`, `portfolio_items`, and `portfolio_item_categories` (pivot) tables and seeding the `portfolio` section + 3 filter categories + 6 gallery items from existing content (guarded, no overwrite of saved data)
- [x] Added `PortfolioCategoryModel`, `PortfolioItemModel`, and `PortfolioItemCategoryModel` (pivot with `useAutoIncrement = false`)
- [x] Added protected admin screen at `/admin/settings/portfolio` with Indonesian labels, validation, and CSRF
- [x] Supported section title, subtitle, layout (masonry 3-col / grid 2-col), and visibility toggle
- [x] Supported filter categories as a separate manager (name + CSS filter class), validated against `^[a-zA-Z][a-zA-Z0-9_-]*$` with unique constraint; deletion blocked while referenced
- [x] Supported item add/edit/delete/ordering (image, alt text, title, optional link, categories) using the shared media selector/upload
- [x] Deleting an item removes only the portfolio item + pivot rows, never the shared media file
- [x] Rendered saved Portfolio content on public homepage while preserving `#portfolio` section, Bootstrap 5 layout, Isotope filtering, and prettyPhoto lightbox
- [x] Hid Portfolio navigation anchor when section is disabled
- [x] Item `link` when empty uses prettyPhoto lightbox on the image; when set, the overlay anchor points to the link (no `data-rel`)
- [x] Verified section/category/item CRUD, ordering, persistence, filtering, visibility, validation, anonymous access denial, and homepage rendering
- [x] Owner reported Portfolio functionality is working (2026-11-02)

### Navigation Responsiveness (Owner Accepted)
- [x] Owner accepted the navigation responsiveness fix (2026-11-02).
- [x] Root cause: the top-nav smooth-scroll ran a **1000 ms** jQuery animation with the `easeInOutExpo` easing, whose leading edge is nearly flat, plus Bootstrap 5's `scroll-behavior: smooth` on `:root` (active under `prefers-reduced-motion: no-preference`) re-smoothed every per-frame `scrollTop` write. Measured effect before the fix: ~950 ms of zero movement after click, then a fast rush.
- [x] Shortened the animation to **300 ms** and switched to `easeOutCubic` (moves on the first frame).
- [x] Cancelled any in-progress/queued animation with `.stop(true)` so rapid clicks respond immediately and only one navigation action runs.
- [x] Forced `document.documentElement.style.scrollBehavior = 'auto'` so jQuery's per-frame scroll writes stay instant (no double-smoothing).
- [x] Respected `prefers-reduced-motion: reduce` by jumping straight to the target.
- [x] Applied the same treatment to the scroll-to-top button.

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
- [x] Portfolio section save (title/subtitle/layout/visibility) persists and renders on the public homepage
- [x] Portfolio category add/rename/delete works; delete is blocked while the category is referenced ("Kategori tidak dapat dihapus karena masih digunakan oleh item portofolio.")
- [x] Portfolio item add/edit/delete/reorder works; item delete preserves the shared media file
- [x] Portfolio validation rejects empty image/categories, invalid filter-class format, and duplicate filter-class
- [x] Portfolio image upload rejects `.php` and `.php.jpg` payloads (extension + MIME); valid PNG upload accepted
- [x] Portfolio visibility toggle hides both the nav anchor and the `#portfolio` section
- [x] Public homepage renders 6 seeded items, 6 images, and filter buttons `*` / `.gal_a` / `.gal_b` / `.gal_c`
- [x] Nav click now responds on the first frame (scroll began at ~28 ms) and settles in ~300 ms; before the fix it held still ~950 ms
- [x] Distant (#contact/#testimonials/#blog), nearby (#home/#about), and logo (#page-top) navigation all land correctly with the fixed-header offset
- [x] Rapid repeated clicks interrupt the previous animation via `.stop(true)` and settle on the last target with no queued runaway
- [x] Scrollspy active-menu highlighting preserved (#home / #testimonials / #contact verified)
- [x] Portfolio Isotope filter verified (`.gal_b` shows 3 items, hides 3) and prettyPhoto `data-rel` overlays intact (6)
- [x] Hidden-section handling: `#services` is absent from nav while disabled and nothing dangling; all rendered anchors resolve to a real element
- [x] Browser console shows zero errors and zero warnings after navigation and filtering

## Known Issues / Console Errors
- One external resource fails in the offline sandbox: `https://fonts.googleapis.com/css?family=Roboto+Mono:300,400,500,700` (imported by `style.css`). This is a network limitation, not a code error.

## Recent Fixes
- Fixed `AdminLoginAttemptModel` to disable automatic timestamps, resolving an `Unknown column 'updated_at' in 'INSERT INTO'` error on failed login attempts.
- Fixed `MediaModel` to disable automatic timestamps (media table only tracks `created_at`).
- Fixed sluggish top navigation: replaced the 1000 ms `easeInOutExpo` smooth-scroll with a 300 ms `easeOutCubic` animation, cancelled prior animation on new clicks (`.stop(true)`), neutralized Bootstrap 5's `scroll-behavior: smooth` so jQuery's per-frame scroll writes stay instant, and added a `prefers-reduced-motion` instant-jump path.

## Manual Acceptance Steps
1. Login at `/admin/login` and open `/admin`.
2. Click **Pengaturan > Identitas Situs**: update site title/name, meta tags, and upload/select logo/favicon/apple-touch-icon; save and verify public homepage reflects changes.
3. Click **Pengaturan > Hero**: update heading, description, rotating words, background image, and optional button; save and verify public homepage reflects changes.
4. Test validation: leave required fields blank and confirm error messages.
5. Test upload rejection: attempt to upload a `.php.jpg` file and confirm it is rejected.
6. Resize browser to mobile width or use device emulation: confirm layout remains responsive and navigation collapses.
7. **About section:** Login and open `/admin/settings/about`. Edit heading, body, image, alt text, button, and layout; save. Confirm the public homepage `#about` section reflects changes and the nav link appears/disappears based on the visibility toggle.
8. **Services section:** Login and open `/admin/settings/services`. Edit section title/subtitle/layout and visibility; add, edit, delete, and reorder service cards using the icon dropdown. Confirm the public homepage `#services` section reflects changes and the nav link disappears when the section is hidden.
9. **Portfolio/Gallery section:** Login and open `/admin/settings/portfolio`. (a) Edit section heading/description/layout/visibility and confirm the public homepage `#portfolio` section and nav anchor update (and disappear when hidden). (b) Add, rename, and delete a filter category; confirm the filter buttons update and deletion of a referenced category is blocked. (c) Add/edit/reorder/delete items with an image, alt text, title, optional link, and categories; confirm Isotope filtering and prettyPhoto lightbox still work, and the nav link disappears when the section is hidden.

## Unverified
- [ ] Mobile viewport rendering (browser automation tools in this session do not support viewport resizing)
- [ ] Physical touch interactions on real mobile devices
- [ ] Mobile-menu closing after a nav click (the collapse-close handler is unchanged and runs with no console errors on desktop, but the reduced/mobile viewport could not be exercised)
- [ ] `prefers-reduced-motion: reduce` runtime path (verified by code inspection only; the emulated environment reports `no-preference`)

## Pending (future tasks)
- [x] Database setup and migrations
- [x] Admin authentication and dashboard
- [x] Site identity and hero settings
- [x] About section (pending owner review/commit)
- [x] Services section (Owner Accepted)
- [x] Portfolio/Gallery section (pending owner review/commit)
- [ ] Category/tag CRUD
- [ ] Media library
- [ ] Article editor with draft/preview/publish
- [ ] Public article listing/detail/search pages
- [ ] Menus and site settings
- [ ] Backup/restore documentation
