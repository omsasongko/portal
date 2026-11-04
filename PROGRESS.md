# Portal — Progress Log

## Last Updated
2026-11-04

## Status
Articles module (admin CRUD + public blog) implemented with a repeat-safe fitness demo, Blog layout settings, menu integration, and a self-hosted TinyMCE editor with full image management (upload, media-library selection, resize presets, alignment, visual cropping, animated GIF support) and YouTube video embedding. Database connection error handling added (HTTP 503 with clean Indonesian error page). Admin interface uses AdminLTE 4.10.1. Pages and Navigation Menu management implemented. All completed modules below are committed/pushed to `origin/main` (except AdminLTE migration, Pages/Navigation, Articles, image management, and error-handling changes which are uncommitted). Backend verified via curl; visual editor interactions require owner manual testing (see Verification section).

## Completed

### Database Connection Error Handling (Ready for Owner Review — Uncommitted)
- [x] Custom `App\Libraries\DatabaseExceptionHandler` implementing `ExceptionHandlerInterface` (framework's `ExceptionHandler` is `final` — cannot be extended)
- [x] `Config\Exceptions::handler()` routes `DatabaseException` with "Unable to connect" to the custom handler; all other exceptions use the default framework handler
- [x] Returns HTTP 503 with a self-contained Indonesian error page: "Maaf, database tidak terhubung." + "Silakan coba beberapa saat lagi." + "Coba Lagi" reload button
- [x] Error page works without database access (plain HTML, no framework views, no config values)
- [x] No stack traces, SQL, credentials, database configuration, or filesystem paths exposed in the response body
- [x] Diagnostic details logged server-side (`log_message('critical', ...)`) with route info; credentials redacted before logging
- [x] Handles failures during filters/authentication (login POST, admin redirect, public pages — all return 503)
- [x] `Cache-Control: no-store` and `noindex, nofollow` meta on the error page
- [x] Normal operation resumes immediately when the database comes back online (verified: all routes return 200 after restart)

### Articles Module & Public Blog (Ready for Owner Review — Uncommitted)
- [x] Migration `2026-11-04-100000_CreateArticlesBlogSection`: `articles` + `article_tags` tables, additive `menu_items.type` ENUM extension (`articles`), guarded `blog` homepage section seed (never overwrites saved settings)
- [x] `ArticleModel`: findBySlug, findPublishedBySlug, latestPublished, publishedPaginated, relatedPublished, tagIdsForArticle, syncTags
- [x] Shared traits extracted (behavior preserved): `Admin\Traits\SanitizesHtml` (tag/attribute allowlist, strips `script`/event handlers/`javascript:`/`data:` URLs) now used by both Pages and Articles; `Admin\Traits\HandlesImageUpload` (extension+MIME allowlist, stores to media library)
- [x] Shared `menu_helper.php` → `build_public_menu(bool $homepageQualified)` used by Home (plain `#anchor` for smooth-scroll), Page, and Blog (homepage-qualified `/#anchor`); auto-omits hidden items, disabled sections, unpublished/missing pages; always keeps the `articles` listing item
- [x] Admin "Artikel" sidebar entry (`bi-newspaper`), list/create/edit/delete, draft/published status, authenticated draft preview rendering the public detail view with a preview banner + `noindex` meta
- [x] Article fields: title, unique editable slug (preserved when only title changes; auto-generated from title when blank), excerpt (≤500), rich-text body, cover image + alt via existing media library/upload, existing category, existing tags (multi-select), publication date (defaults to today when publishing without a date)
- [x] Per-article detail layout radio: `full` (wide reading area) or `sidebar` (reading area + related-articles sidebar) — predefined responsive templates only
- [x] Deleting an article removes its tag links + row but never deletes shared media (cover stays in the library)
- [x] Self-hosted TinyMCE 6.8.5 at `public/assets/admin/vendor/tinymce/` (GPL `license_key: 'gpl'`, no external API key/CDN) with Indonesian language pack `langs/id.js` (from npm `tinymce-i18n`); `valid_elements` mirrors the server-side allowlist; page_form.php also switched to the local build
- [x] Public routes: `/blog` (paginated listing, 6/page, published only) and `/blog/{slug}` (detail); draft/deleted/missing → 404; reuse public styling/header/footer/dynamic nav
- [x] Homepage Blog placeholders replaced with a dynamic `$blogEnabled`-guarded section: title/subtitle from settings, `card_layout` → 2/3 columns, `limit` newest published articles, "Belum ada artikel" empty state, "Lihat semua artikel" link
- [x] Blog settings screen (Indonesian) at `/admin/settings/blog`: title, subtitle, visibility toggle, homepage article limit (1–12), 2/3-column cards; includes an info alert explaining that menu ordering, homepage section ordering, Blog card layout, and per-article detail layout are separate settings
- [x] Menu manager gains type "Daftar Artikel (Blog)" (fixed target `blog`, links to `/blog`); existing "Blog" section menu target (`#blog`) keeps working
- [x] Fitness demo CLI `php spark demo:fitness` (never runs in migrations): idempotent by slug/filename; creates Fitness category, 3 tags (Gym/Jurnal Latihan/Perlengkapan Latihan), copies 3 local Unsplash cover photos into `uploads/media/` and registers them, inserts 3 clearly-marked demo articles (2 published, 1 draft) with tag sync; sources/license recorded in `public/assets/portal/uploads/demo/README.md`; editorial content, no medical claims
- [x] Category/tag deletion guards (previously dormant pending `articles`/`article_tags`) are now active and verified: deletion is blocked with "masih digunakan oleh artikel" while referenced

### Article Image Management & YouTube Embedding (Ready for Owner Review — Uncommitted)

**Manual acceptance findings addressed:**
- ✗ Editor's Insert/Edit Image dialog only offered a Source URL — now replaced with "Unggah Gambar" and "Pilih dari Pustaka" toolbar buttons
- ✗ Cover upload showed incorrect "2 MB, JPG/PNG/WebP/ICO" — now shows "50 KB – 10 MB, JPG/PNG/GIF/WebP"
- ✗ No inline image management — now has upload, library selection, resize, alignment, crop, and YouTube embed

**Backend endpoints (all inside admin auth group):**
- [x] `POST articles/upload-image` — multipart file upload, validates extension+MIME+size+content via getimagesize(), stores in media library, returns JSON `{location: "..."}`
- [x] `POST articles/crop-image` — accepts JSON with src/x/y/width/height, validates coordinates within image bounds, creates new media file preserving format/transparency, rejects animated GIF crop with Indonesian explanation
- [x] `GET articles/media-library` — returns JSON array of all image media items (newest first)
- [x] `POST articles/embed-youtube` — validates URL, extracts video ID (watch/youtu.be/shorts), returns responsive youtube-nocookie.com iframe HTML

**Upload rules (HandlesImageUpload trait updated):**
- [x] Allowed extensions: jpg, jpeg, png, gif, webp (ICO removed)
- [x] Both MIME-detected extension AND client filename extension validated
- [x] Minimum 50 KB (51,200 bytes), maximum 10 MB (10,485,760 bytes)
- [x] getimagesize() content validation to reject disguised/corrupt files
- [x] PHP web runtime: upload_max_filesize=10M, post_max_size=16M — sufficient, no changes needed

**HTML sanitization (SanitizesHtml trait extended):**
- [x] `img` allows: src, alt, title, width, height, style, class
- [x] `div` allows: class, style
- [x] `iframe` added to allowed tags: src (restricted to youtube-nocookie.com/embed/ only), width, height, frameborder, allow, allowfullscreen, title, class, style
- [x] Style sanitization: whitelist of CSS properties (width, max-width, height, display, margin, float, text-align, position, padding-bottom, overflow, top, left, aspect-ratio, border)
- [x] Class filtering: img classes limited to img-fluid, img-align-left/center/right; div classes limited to video-embed-wrapper, img-align-left/center/right
- [x] Scripts, event handlers, javascript:/vbscript:/data: URLs, arbitrary iframe sources all rejected

**TinyMCE editor integration (article_form.php):**
- [x] Custom toolbar buttons: "Unggah Gambar" (file upload), "Pilih dari Pustaka" (media library grid dialog)
- [x] Image alignment buttons: left/center/right via CSS classes
- [x] Resize preset buttons: 25%, 50%, 75%, 100% of content width
- [x] "Potong" crop button with visual drag-select overlay dialog, Apply/Cancel, Indonesian coordinate display
- [x] "YouTube" embed button with URL input dialog, Indonesian help text about supported formats and internet requirement
- [x] Click to select image, double-click for alt text edit + remove dialog
- [x] CSRF token sent as X-CSRF-TOKEN header for JSON endpoints; cookie-based refresh for regenerate=true
- [x] valid_elements updated to match server-side allowlist
- [x] content_style provides WYSIWYG preview of alignment/resize/video in editor

**Public/preview CSS (blog_detail.php):**
- [x] `.article-body img.img-align-left/center/right` for image alignment
- [x] `.article-body .video-embed-wrapper` with responsive 56.25% padding-bottom pattern
- [x] `max-width: 100%; height: auto;` on all article body images

**Animated GIF handling:**
- [x] Upload and display: animated GIFs upload normally and animation is preserved (GIF format stored as-is)
- [x] Crop rejection: binary GIF parser counts image descriptors (0x2C); >1 frame returns "Pemotongan tidak tersedia untuk GIF animasi. Gunakan gambar statis."
- [x] Resize/alignment still work for animated GIFs (CSS-only, no re-encoding)

### AdminLTE 4.10.1 Admin Interface Migration (Ready for Owner Review)
- [x] Template: AdminLTE v4.10.1 (MIT, ColorlibHQ), Bootstrap 5.3.8 bundled in adminlte.css, no jQuery dependency
- [x] Dependencies loaded via CDN: Source Sans 3 font (@fontsource 5.3.0), OverlayScrollbars 2.16.0, Bootstrap Icons 1.13.1, Popper.js 2.11.8, Bootstrap 5.3.8 JS
- [x] Local assets copied to `public/assets/admin/` (css/adminlte.min.css, js/adminlte.min.js, img/AdminLTELogo.png); `adm-back/` kept intact
- [x] Admin assets fully separated from public homepage assets (`public/assets/portal/`)
- [x] Created reusable `admin/layout.php` with AdminLTE sidebar, header, content, footer structure
- [x] Created `admin/auth_layout.php` for login page (centered card, no sidebar)
- [x] Sidebar navigation: Dasbor, Pengaturan (treeview with 6 children: Identitas Situs, Hero, About, Layanan, Portofolio, Testimoni), Kategori, Tag, Ubah Kata Sandi
- [x] Active menu detection: sidebar highlights current page, Pengaturan treeview auto-opens when a child is active
- [x] Sidebar toggle (hamburger) working; dark sidebar theme with light content area
- [x] Header: "Hai, {admin_name}" greeting and Logout button (POST with CSRF)
- [x] Content header with page title and breadcrumb on all pages
- [x] Flash messages (success/error) with dismiss buttons in content area
- [x] Dashboard: 4 AdminLTE small-box widgets with real data (Identitas Situs, Bagian Homepage, Media, Kategori & Tag)
- [x] Login page: AdminLTE centered login-box with Bootstrap Icons (person, lock), Indonesian labels
- [x] All 11 admin screens migrated: login, dashboard, change password, identity, hero, about, services, portfolio, testimonials, categories, tags
- [x] AdminLTE card structure (card-header with card-title, card-body) on all settings/CRUD pages
- [x] Tables use `table-striped` for better readability
- [x] All existing functionality preserved: routes, authentication, CSRF, validation, field names, CRUD, uploads, media selectors, ordering, visibility controls, saved data
- [x] Indonesian labels and messages preserved throughout
- [x] Entered values preserved and validation errors displayed when forms fail (old() values + is-invalid classes)
- [x] Services icon preview script moved to `scripts` section (proper AdminLTE pattern)
- [x] Public homepage unchanged — no regression (all sections render, own Bootstrap 5 assets unaffected)
- [x] No database schema or data changes for this visual migration
- [x] Zero console errors across all admin pages
- [x] Zero failed network requests (all CDN + local assets load)
- [x] Protected routes redirect anonymous users to login (verified via curl)

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

### Testimonials Section (Ready for Owner Review)
- [x] Added additive migration `2026-11-02-153500_CreateTestimonialsSeed.php` seeding the `testimonials` section (`sort_order = 4`) + 6 default cards from existing content, plus 3 shared avatar media rows (guarded — no overwrite of saved data)
- [x] Reused `homepage_section_items` storage (no new tables); card content JSON keys `quote_heading`, `quote_body`, `author_name`, `author_role`, `avatar`
- [x] Added protected admin screen at `/admin/settings/testimonials` with Indonesian labels, server-side validation, and CSRF
- [x] Supported section title, subtitle, layout (`carousel_2_per_slide` default / `carousel_1_per_slide`), and visibility toggle
- [x] Supported testimonial card add/edit/delete/ordering with avatar via the shared media selector/upload
- [x] Deleting a testimonial removes only the item row, never the shared media/avatar file
- [x] Rendered saved Testimonials content on the public homepage while preserving `#testimonials`, Bootstrap 5 layout, and the Owl Carousel loop
- [x] `carousel_1_per_slide` emits `data-per-slide="1"`; `custom.js` reads it per carousel via `.each()`
- [x] Hid the Testimonials navigation anchor when the section is disabled
- [x] Home controller loads the section + items for public rendering

### Article Categories & Tags (Owner Accepted)
- [x] Owner accepted Categories and Tags management (2026-11-02).
- [x] Added additive migration `2026-11-02-165327_CreateCategoriesTags.php` creating `categories` and `tags` tables (id, name, unique slug, optional description, timestamps) — separate from `portfolio_categories`
- [x] Added `CategoryModel` and `TagModel`
- [x] Added protected admin screens at `/admin/categories` and `/admin/tags` with Indonesian labels, server-side validation, CSRF, and esc() output
- [x] Supported name (required), unique slug, and optional description for both; slug auto-generated from name when blank and editable
- [x] Renaming does not silently change an existing slug (slug field is pre-filled and preserved on edit)
- [x] Slug normalized lowercase (`mb_url_title`), validated `^[a-z0-9]+(?:-[a-z0-9]+)*$`, and uniqueness enforced (excluding self)
- [x] Deletion is blocked while referenced via a `tableExists()` guard against future `articles.category_id` / `article_tags.tag_id` (per task requirement; not yet exercisable because those tables do not exist)
- [x] Added admin navigation links: "Kategori" and "Tag" in the admin sidebar nav

### Pages & Navigation Menu (Ready for Owner Review)
- [x] Added additive migration `2026-11-03-100000_CreatePagesMenuItems.php` creating `pages` table (id, title, slug unique, content text, status enum draft/published, timestamps) and `menu_items` table (id, label, type enum section/page/url, target, sort_order, is_visible, timestamps)
- [x] Seeded 7 default menu items matching existing hardcoded navigation (Home, About Us, Services, Portfolio, Testimonials, Blog, Contact Us) — guarded by empty-table check
- [x] Added `PageModel` and `MenuItemModel`
- [x] Added protected admin Pages CRUD at `/admin/pages` with create/edit/delete, Indonesian labels, server-side validation, CSRF, and esc() output
- [x] Pages: title (required), unique editable slug (auto-generated from title when blank), content with rich-text editor (TinyMCE 6 CDN, GPL), draft/published status
- [x] Slug preserved when title changes unless explicitly edited; slug validated `^[a-z0-9]+(?:-[a-z0-9]+)*$` and uniqueness enforced
- [x] Content sanitized server-side with allowlist: allowed tags (p, br, strong, b, em, i, u, s, sub, sup, h1-h6, ul, ol, li, a, img, blockquote, pre, code, table, thead, tbody, tfoot, tr, th, td, hr, div, span); allowed attributes per tag; `javascript:`/`vbscript:`/`data:` URIs blocked; `<script>` tags stripped; event handler attributes (`onmouseover`, `onerror`, etc.) stripped
- [x] Public pages at `/pages/{slug}` — only `status = 'published'` pages are accessible; drafts and deleted pages return 404
- [x] Public page view reuses homepage header/footer styling with shared navigation; standalone pages always show shrunk navbar
- [x] Section links from standalone pages use homepage-qualified format (`/#about`, `/#portfolio`) for full-page navigation
- [x] Added protected admin Menu management at `/admin/menu` with add/edit/delete, visibility toggle, up/down ordering, Indonesian labels, server-side validation, CSRF
- [x] Menu targets: homepage section (validated against known sections), published page (slug validated against pages table), or external HTTP/HTTPS URL (validated)
- [x] Menu labels are independent of page/section titles
- [x] Deleting a menu item does not delete its target content (page or section)
- [x] Auto-omit logic: hidden items (is_visible=0), hidden homepage sections (is_enabled=0), and unpublished/deleted pages are excluded from public navigation
- [x] External URLs open with `target="_blank" rel="noopener"`
- [x] Homepage navigation now dynamic from menu_items; footer links also dynamic
- [x] Homepage scroll behavior preserved: 300ms smooth scrolling, fixed-header offset, active highlighting (ScrollSpy), mobile collapse — `js-scroll-trigger` class applied only to section links
- [x] Admin sidebar: added "Halaman" and "Menu" nav items with active highlighting
- [x] Dashboard updated with Pages and Menu counts

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

### Database Error Handling (2026-11-04, via curl with DB stopped/restarted)

### Image Management & YouTube Embedding (2026-11-04, via curl)
**Upload endpoint (POST articles/upload-image):**
- [x] Valid JPEG (227 KB) uploaded → JSON `{location: "..."}` returned, file stored in media library
- [x] Valid PNG with transparency (166 KB) uploaded successfully
- [x] Valid GIF (static, 275 KB) uploaded successfully
- [x] Animated GIF (51 KB, 2 frames) uploaded successfully (animation preserved as raw file)
- [x] File < 50 KB (691 bytes) → rejected: "Ukuran file terlalu kecil. Minimum 50 KB."
- [x] File with .ico extension (JPEG content) → rejected: "Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP."
- [x] PHP script disguised as .jpg → rejected: "Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP." (MIME detection catches it)
- [x] File over 10 MB → rejected before validation (PHP upload_max_filesize limit)

**Crop endpoint (POST articles/crop-image):**
- [x] Valid JPEG crop → new file created with random name, original file preserved
- [x] Static GIF crop → works, preserves GIF format
- [x] PNG crop → works, preserves format (transparency preservation via alpha channel)
- [x] Animated GIF crop → rejected: "Pemotongan tidak tersedia untuk GIF animasi. Gunakan gambar statis."
- [x] Out-of-bounds coordinates → rejected: "Area pemotongan melebihi dimensi gambar."
- [x] Original media file preserved after crop (verified: both files exist with different sizes)

**Media library endpoint (GET articles/media-library):**
- [x] Returns JSON array of all image media items (17+ items) with url/title/alt
- [x] Ordered by id DESC (newest first)
- [x] Only image MIME types included

**YouTube embed endpoint (POST articles/embed-youtube):**
- [x] `youtube.com/watch?v=ID` → valid, returns responsive iframe HTML with youtube-nocookie.com
- [x] `youtu.be/ID` → valid
- [x] `youtube.com/shorts/ID` → valid
- [x] Invalid URL (`notreal.com`) → rejected: "URL YouTube tidak valid. Gunakan link dari youtube.com atau youtu.be."

**Sanitization:**
- [x] `<script>alert(1)</script>` → script tag stripped, text content preserved
- [x] `<img onerror="alert(2)">` → onerror handler stripped, img tag preserved
- [x] `<iframe src="https://evil.com">` → src stripped (only youtube-nocookie.com/embed/ allowed)
- [x] Image alignment classes (img-align-center), styles (width: 50%), and YouTube embeds all survive sanitization

**Save/reopen persistence:**
- [x] Article with inline image (class, style, alt) + YouTube embed saved → HTTP 303 redirect
- [x] Reopened in edit form → all image settings and YouTube embed preserved in textarea
- [x] Public article page renders all alignment CSS, video wrapper, and responsive styles

**Regression (no breakage):**
- [x] Homepage → 200; Blog list → 200; Existing article detail → 200; Page → 200
- [x] Admin articles/pages/menu → 200; Preview → 200
- [x] Data intact: 3 pages, 7 menu items, 4 articles (3 original + 1 test), 1 category, 3 tags

**Requires owner manual testing (visual interactions not exercisable via curl):**
- [ ] Click "Unggah Gambar" button in editor toolbar → file picker opens → image inserts at cursor
- [ ] Click "Pilih dari Pustaka" button → media grid dialog → click image → inserts at cursor
- [ ] Click image in editor → image selected (blue handles visible)
- [ ] Double-click image → alt text dialog opens → edit alt → save → alt persists
- [ ] Double-click image → "Hapus Gambar" button → image removed from content, media file preserved
- [ ] Click alignment buttons (left/center/right) with image selected → alignment changes visually in editor
- [ ] Click resize buttons (25%/50%/75%/100%) with image selected → width changes visually in editor
- [ ] Click "Potong" with image selected → crop overlay appears → drag selection → "Terapkan" → image src updated to cropped version
- [ ] Click "Potong" → "Batal" → image unchanged
- [ ] Click "YouTube" → enter URL → "Sematkan" → responsive video player appears in editor
- [ ] Upload failure (e.g. network error) → error notification shown, article content preserved
- [ ] Animated GIF uploaded → plays animation in editor and public page
- [ ] Animated GIF → "Potong" → rejected with "Pemotongan tidak tersedia untuk GIF animasi."
- [ ] Cover upload independent from inline images (changing cover doesn't affect body images)
- [ ] Mobile/responsive rendering of aligned images and video embeds
- [x] DB stopped → `GET /` returns HTTP 503 with clean Indonesian error page (2557 bytes, self-contained HTML)
- [x] DB stopped → `GET /blog`, `GET /blog/{slug}`, `GET /pages/{slug}`, `POST /admin/login` all return HTTP 503
- [x] DB stopped → `GET /admin/login` returns 200 (no DB hit needed for form render); `GET /admin`, `GET /admin/articles` return 302 (auth filter redirect, no DB hit)
- [x] 503 response body contains zero matches for: stack trace, exception class names, APPPATH/ROOTPATH/FCPATH, filesystem paths (/Volumes, /var, /tmp), database host/port/name (mariadb, 3307, portal_db), credentials, .env, PHP filenames, or trace line numbers
- [x] Server-side log contains `CRITICAL` entries with route info (`GET /`, `POST admin/login`, etc.) and the message "Unable to connect to the database" — no credentials in log
- [x] Response headers include `Cache-Control: no-store` and status line `HTTP/1.1 503 Service Unavailable`
- [x] DB restarted → all routes immediately recover (homepage 200, blog 200, admin login 200, article detail 200, page detail 200)
- [x] Existing data fully intact after recovery (3 pages, 7 menu items, 17 media, 3 articles, 1 category, 3 tags)
- [x] PHP lint clean on both `app/Libraries/DatabaseExceptionHandler.php` and `app/Config/Exceptions.php`

### Articles Module (2026-11-04, via curl + browser)
- [x] Anonymous access to `/admin/articles`, `/admin/articles/create`, `/admin/articles/preview/{id}`, `/admin/settings/blog` → 302 to login
- [x] `php spark demo:fitness` run twice: first creates 3 articles + Fitness category + 3 tags + 3 media; second run skips all (0 created, 3 skipped) — idempotent
- [x] Public `/blog` lists only the 2 published demo articles (draft excluded); `/blog/{published-slug}` → 200; `/blog/{draft-slug}` → 404; `/blog/{missing-slug}` → 404
- [x] Detail layout switching: `sidebar` article renders col-lg-8 + "Artikel Terkait"; `full` article renders col-12, no related sidebar
- [x] Cover images render on listing/detail; media file served (HTTP 200)
- [x] Article create draft → 303; body XSS sanitized server-side (`<script>`, `onerror`, `onclick`, `javascript:` href all stripped)
- [x] Duplicate slug on create → rejected (redirects back to create form, no new row)
- [x] Editing title only → slug preserved (`artikel-uji-verifikasi` unchanged after title edit)
- [x] Cover upload: `.php` rejected (cover stays NULL); valid JPG accepted, stored in `uploads/media/`, registered in `media` table; library radio selection also works
- [x] Draft preview: anonymous → 302; authenticated → 200 with "Pratinjau admin" banner + `noindex, nofollow` meta
- [x] Publish → `/blog/{slug}` 200 and appears in listing; unpublish → 404 again
- [x] Delete article → removes article + its `article_tags` rows, keeps `media` rows/files (shared cover not deleted); public detail → 404
- [x] Blog settings save: title/subtitle/limit/columns/visibility persist; homepage honors 2 vs 3 columns, limit, and hides `#blog` section + its nav link when disabled
- [x] Menu type "Daftar Artikel (Blog)" adds a `/blog` link to public nav on homepage and standalone pages; temp item removed after test (menu back to 8 items)
- [x] Category/tag deletion blocked while referenced ("masih digunakan oleh artikel"); counts unchanged after attempt
- [x] Pagination: temporarily inserted 5 extra published articles → `/blog` shows page 2 link, `?page=2` renders overflow; temp rows removed after test
- [x] Regression: `/`, `/blog`, `/pages/{published}`, all admin list/create screens → 200; homepage renders all sections (home/about/services/portfolio/testimonials/blog/contact); Pages save still sanitizes after trait refactor; `js-scroll-trigger` 300 ms nav links intact
- [x] Zero console errors on article form (TinyMCE initializes, `language: 'id'`, 1 iframe), `/blog`, `/blog/{slug}`, `/`, `/pages/{slug}`, `/admin/settings/blog`, `/admin/articles`
- [x] Fixed during verification: `Articles::delete()` referenced undefined `$this->db` (500) → switched to `\Config\Database::connect()`; deleted test artifacts from media library afterward

### Earlier Milestones
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
- [x] Testimonials migration seeds 6 cards (item ids 9–14, section_id 5) and 3 avatar media rows without overwriting existing data
- [x] Testimonials section save (title "Kata Mereka", layout `carousel_1_per_slide`) persists; `data-per-slide` switches 2→1 then restored to 2
- [x] Testimonial card add registers avatar upload in `media`; edit updates content; reorder updates positions; delete removes only the row (media row + file preserved)
- [x] Testimonials validation rejects empty `quote_body`/`author_name` and invalid `layout` (`in_list`)
- [x] Avatar upload rejects a `.php` payload ("… tidak diizinkan"); valid PNG accepted
- [x] Anonymous GET `/admin/settings/testimonials` → 302 login; POST → 403
- [x] Visibility toggle hides both the nav anchor and the `#testimonials` section when disabled
- [x] Browser shows `.owl-loaded`, `owlItemsPerSlideSettings: 2`, 12 `.testimonial` nodes (6 + Owl loop clones), 0 console errors/warnings
- [ ] Scrollspy active-menu highlighting: confirmed correct with fully-settled scrolling (direct `scrollTo` and nav-click both yield `#home`/`#about`/`#testimonials`/`#contact` after ~1.6 s). An earlier short-settle (~450–600 ms) automation read `.active` stuck on `#home`; this did not reproduce with adequate settle time and is attributed to a measurement timing artifact, not a code regression. No code change was required, and this remains not fully eliminated (see Known Issues).
- [x] Categories/tags migration creates `categories` and `tags` tables with a unique `slug`; empty at first run (no seed), no `portfolio_categories` impact
- [x] Category create with blank slug auto-generates `web-development`; tag create with blank slug auto-generates `teknologi`
- [x] Duplicate slug rejected for both ("Slug sudah digunakan oleh kategori/tag lain")
- [x] Category/tag rename preserves the existing slug (name changed, slug unchanged)
- [x] Empty name rejected ("The name field is required"); provided slug normalized to lowercase
- [x] Category/tag edit and delete work; tables return to 0 rows after cleanup
- [x] Anonymous GET `/admin/categories` and `/admin/tags` → 302 login; anonymous POST → 403
- [x] Public homepage regression: all sections (#home/#about/#portfolio/#testimonials/#blog/#contact) render; testimonials still shows 6 cards / `data-per-slide="2"` / nav anchor present
- [x] Pages migration creates `pages` and `menu_items` tables; seeds 7 default menu items matching hardcoded navigation
- [x] Page create with blank slug auto-generates from title ("Tentang Kami" → `tentang-kami`)
- [x] Duplicate slug rejected on page create (redirects back to form)
- [x] Page edit: title changed ("Tentang Portal Kami") but slug (`tentang-kami`) preserved when not modified
- [x] Draft page returns 404 at `/pages/halaman-draf`; published page returns 200 at `/pages/tentang-kami`
- [x] Nonexistent page slug returns 404
- [x] Content sanitization: `<script>` tags stripped, `javascript:` URLs removed, `onerror`/`onmouseover` event handlers stripped, safe content (`<p>`, `<strong>`, `<img>`) preserved
- [x] Menu item add (page type): "Tentang" menu pointing to `tentang-kami` page appears in public nav
- [x] Menu item add (URL type): "GitHub" menu pointing to `https://github.com` appears with `target="_blank" rel="noopener"`
- [x] Menu visibility toggle: hiding "Blog" removes it from public nav; re-showing restores it
- [x] Menu move up/down: "Contact Us" swapped with "Blog" via move-up; restored via move-down
- [x] Auto-omit: disabling `services` section in `homepage_sections` removes "Services" from public nav (restored after)
- [x] Auto-omit: setting page to `draft` status removes page-type menu item from public nav (restored after)
- [x] Menu item deletion does not delete target page (pages table unchanged after menu item deletion)
- [x] Public page view: title, content, nav with homepage-qualified `/#section` links, footer with dynamic menu, navbar always shrunk
- [x] Homepage dynamic navigation: 7 menu items render with `js-scroll-trigger` class for section links only
- [x] Homepage footer: dynamic menu links match nav
- [x] Anonymous GET `/admin/pages` → 302 login; anonymous GET `/admin/menu` → 302 login
- [x] Admin sidebar: "Halaman" and "Menu" nav items present and render correctly
- [x] Zero console errors on homepage, admin pages list, admin menu list, and public page view

## Known Issues / Console Errors
- One external resource fails in the offline sandbox: `https://fonts.googleapis.com/css?family=Roboto+Mono:300,400,500,700` (imported by `style.css`). This is a network limitation, not a code error.
- Scrollspy `.active` highlight is correct after navigation settles (~1.6 s), but an early automated read shortly after a nav click (~450–600 ms) momentarily showed `.active` stuck on `#home`. This is attributed to IntersectionObserver settling time during the 300 ms jQuery animation, not a regression; no fix was applied. Worth a quick manual spot-check that the active menu item tracks the section while scrolling.

## Recent Fixes
- Fixed `AdminLoginAttemptModel` to disable automatic timestamps, resolving an `Unknown column 'updated_at' in 'INSERT INTO'` error on failed login attempts.
- Fixed `MediaModel` to disable automatic timestamps (media table only tracks `created_at`).
- Fixed sluggish top navigation: replaced the 1000 ms `easeInOutExpo` smooth-scroll with a 300 ms `easeOutCubic` animation, cancelled prior animation on new clicks (`.stop(true)`), neutralized Bootstrap 5's `scroll-behavior: smooth` so jQuery's per-frame scroll writes stay instant, and added a `prefers-reduced-motion` instant-jump path.

## Manual Acceptance Steps
0. **AdminLTE migration (visual):** Open each admin screen and confirm AdminLTE sidebar layout renders correctly: sidebar toggle, dark sidebar with navigation, active menu highlighting, breadcrumbs, card-based content, footer. Confirm no Bootstrap/AdminLTE console errors in browser dev tools. Confirm the public homepage (`/`) is visually unchanged.
1. Login at `/admin/login` and open `/admin`.
2. Click **Pengaturan > Identitas Situs**: update site title/name, meta tags, and upload/select logo/favicon/apple-touch-icon; save and verify public homepage reflects changes.
3. Click **Pengaturan > Hero**: update heading, description, rotating words, background image, and optional button; save and verify public homepage reflects changes.
4. Test validation: leave required fields blank and confirm error messages.
5. Test upload rejection: attempt to upload a `.php.jpg` file and confirm it is rejected.
6. Resize browser to mobile width or use device emulation: confirm layout remains responsive and navigation collapses.
7. **About section:** Login and open `/admin/settings/about`. Edit heading, body, image, alt text, button, and layout; save. Confirm the public homepage `#about` section reflects changes and the nav link appears/disappears based on the visibility toggle.
8. **Services section:** Login and open `/admin/settings/services`. Edit section title/subtitle/layout and visibility; add, edit, delete, and reorder service cards using the icon dropdown. Confirm the public homepage `#services` section reflects changes and the nav link disappears when the section is hidden.
9. **Portfolio/Gallery section:** Login and open `/admin/settings/portfolio`. (a) Edit section heading/description/layout/visibility and confirm the public homepage `#portfolio` section and nav anchor update (and disappear when hidden). (b) Add, rename, and delete a filter category; confirm the filter buttons update and deletion of a referenced category is blocked. (c) Add/edit/reorder/delete items with an image, alt text, title, optional link, and categories; confirm Isotope filtering and prettyPhoto lightbox still work, and the nav link disappears when the section is hidden.
10. **Testimonials section:** Login and open `/admin/settings/testimonials`. (a) Edit section title/subtitle, switch layout between 2-per-slide and 1-per-slide, and toggle visibility; confirm the public homepage `#testimonials` section, carousel slide count, and nav anchor update (and disappear when hidden). (b) Add, edit, reorder, and delete a testimonial card (quote heading, quote body, author name, role, avatar); confirm the carousel updates and that deleting a card does not remove the shared avatar image. (c) Attempt to upload a non-image file as avatar and confirm rejection; leave required fields empty and confirm error messages.
11. **Categories:** Login and open `/admin/categories` (nav "Kategori"). (a) Add a category and leave the slug blank, confirm a slug is auto-generated from the name. (b) Edit the name only; confirm the slug is unchanged. (c) Try to add a second category with the same slug; confirm it is rejected. (d) Delete an unused category; confirm it disappears.
12. **Tags:** Login and open `/admin/tags` (nav "Tag"). Repeat the same add / rename-preserves-slug / duplicate-slug-rejection / delete flow as categories.
13. **Pages:** Login and open `/admin/pages` (nav "Halaman"). (a) Click "Tambah Halaman": enter a title, leave slug blank, set status to "Terbit", type some rich-text content in TinyMCE, and save. Confirm the page appears in the list with auto-generated slug. (b) Edit the page: change the title only; confirm the slug is preserved. (c) Try to create a second page with the same slug; confirm rejection. (d) Set a page to "Draf" status; confirm `/pages/{slug}` returns 404. Set back to "Terbit"; confirm the page loads at `/pages/{slug}`. (e) Open the public page and confirm: page title, content, navigation with homepage-qualified `/#section` links, footer links, navbar always shrunk. (f) Delete a page and confirm it disappears from the list and returns 404 publicly.
14. **Menu:** Login and open `/admin/menu` (nav "Menu"). (a) Confirm 7 seeded items matching existing navigation. (b) Add a new "Bagian Homepage" menu item; confirm it appears on the public homepage. (c) Add a new "Halaman" menu item pointing to a published page; confirm it appears in nav. (d) Add a "URL Eksternal" menu item with an `https://` URL; confirm it opens in a new tab. (e) Use up/down arrows to reorder; confirm the public nav order changes. (f) Toggle visibility on a menu item; confirm it disappears/reappears in public nav. (g) Edit a menu item label; confirm the label changes without affecting the target. (h) Delete a menu item; confirm it disappears from nav but the target page/section is unaffected. (i) Hide a homepage section (e.g., disable Services via `/admin/settings/services`); confirm the corresponding menu item is auto-omitted from public nav even though it's still visible in admin. (j) Set a page linked by a menu item to "Draf"; confirm the menu item is auto-omitted from public nav.
15. **Homepage regression:** After all changes, confirm the public homepage at `/` renders all sections, smooth scrolling works (300ms), navbar shrinks on scroll, mobile menu toggles, scroll-to-top works, and the footer shows dynamic links.
16. **Articles:** Run `php spark demo:fitness` (repeat-safe). Login and open `/admin/articles` (nav "Artikel"). (a) Confirm the 3 demo articles appear (2 "Terbit", 1 "Draf") with the Fitness category. (b) Click "Tambah Artikel": enter a title, leave the slug blank (auto-generated), pick the Fitness category, tick tags, type rich-text in the Indonesian TinyMCE editor, choose a cover from the media library or upload one, keep status "Draf", and save. (c) Edit it: change only the title and confirm the slug is preserved; try setting a duplicate slug and confirm rejection. (d) Click the eye (Pratinjau) button: confirm the draft renders with the "Pratinjau admin" banner; open the same preview in a logged-out browser and confirm it redirects to login. (e) Set status to "Terbit" and save; confirm the article appears at `/blog` and in the homepage Blog section, and `/blog/{slug}` loads. (f) Switch its detail layout between "Area baca + sidebar" and "lebar penuh" and confirm the public detail page changes. (g) Set back to "Draf"; confirm `/blog/{slug}` returns 404 and it disappears from the listing. (h) Delete it; confirm the cover image is still present in the media library (used by other forms).
17. **Blog settings:** Open `/admin/settings/blog` (Pengaturan > Blog). Change title/subtitle, switch 3→2 columns, change the homepage limit, and toggle visibility off; confirm the homepage `#blog` section reflects each change and its nav link disappears when hidden. Read the info alert explaining the four separate layout settings. Restore afterwards.
18. **Menu → article listing:** Open `/admin/menu`, add an item of type "Daftar Artikel (Blog)" with an Indonesian label; confirm it links to `/blog` in the public nav (homepage and standalone pages). Delete it afterwards if it was only a test.
19. **Category/tag policy:** Try to delete the Fitness category or a tag used by a demo article (`/admin/categories`, `/admin/tags`); confirm it is blocked with "masih digunakan oleh artikel". Remove the reference from the articles first if deletion is truly wanted.

## Unverified
- [ ] AdminLTE sidebar behavior on mobile viewport (sidebar collapse/overlay mode not tested via browser tools — desktop toggle confirmed working)
- [ ] AdminLTE dark/light theme toggle (theme-init script included but no toggle UI was added — defaults to system preference)
- [ ] Mobile viewport rendering (browser automation tools in this session do not support viewport resizing)
- [ ] Physical touch interactions on real mobile devices
- [ ] Mobile-menu closing after a nav click (the collapse-close handler is unchanged and runs with no console errors on desktop, but the reduced/mobile viewport could not be exercised)
- [ ] `prefers-reduced-motion: reduce` runtime path (verified by code inspection only; the emulated environment reports `no-preference`)
- [ ] TinyMCE rich-text editor visual rendering and toolbar interaction (local build initializes with zero console errors and the `id` language pack loads, but automated tooling cannot interact with the TinyMCE iframe — content submission + server-side sanitization are verified)
- [ ] Public page mobile viewport layout (page-content-wrapper responsive CSS verified by inspection but not viewport-tested)
- [ ] `/blog` listing + article detail mobile viewport rendering (responsive Bootstrap grid by inspection; not viewport-tested)

## Next Milestone (Suggested)
- [ ] Media library management screen (standalone `/admin/media` list with delete — media rows are currently created by uploads and the picker, but there is no management UI)
- [ ] Public article search / category-tag archive pages (listing is currently chronological only)
- [ ] Backup/restore documentation

## Pending (future tasks)
- [x] Database setup and migrations
- [x] Admin authentication and dashboard
- [x] Site identity and hero settings
- [x] About section (pending owner review/commit)
- [x] Services section (Owner Accepted)
- [x] Portfolio/Gallery section (pending owner review/commit)
- [x] Testimonials section (pending owner review/commit)
- [x] Category/tag CRUD (Owner Accepted)
- [x] Pages CRUD with rich-text editor (pending owner review/commit)
- [x] Navigation menu management (pending owner review/commit)
- [ ] Media library
- [x] Article editor with draft/preview/publish (pending owner review/commit)
- [x] Public article listing/detail pages (pending owner review/commit) — search not included
- [ ] Backup/restore documentation
