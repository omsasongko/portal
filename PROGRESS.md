# Portal — Progress Log

## Last Updated
2026-11-07

### Security review: public Blog filters (2026-11-07) — findings + fixes, uncommitted
Focused review of `categories`, `tags`, `q`, `page`, legacy `category`/`tag`, and the async `/blog/results` endpoint against the running app (`127.0.0.1:8081`). Findings below — severity / affected file / evidence / fix / retest. Fixes are code-only (no article data or settings changed); the two temporary draft rows used for verification were deleted afterwards.

#### Confirmed issues — FIXED
- **HIGH — HTTP 500 on array / nested-array parameters** — `app/Controllers/Blog.php`.
  - Evidence: `?q[]=a` → 500 `ErrorException: Array to string conversion` (line 162); `?page[]=1` → 500 (line 191); `?categories[a][b]=x` → 500.
  - Fix: new `scalarParam()` coerces an array/null to `''` (so `q` degrades gracefully); `tokensFromParam()` now skips nested arrays; `page` is derived from a checked scalar string.
  - Retest: `q[]=a`, `q[a][b]=a`, `categories[a][b]=x`, `categories[][][]=x`, `tags[]=kardio&tags[x]=zzz`, `categories[]=teknologi&categories[a]=x`, `page[]=1`, `page[a]=1` all return 200/302 with zero `TypeError`/`ErrorException`/`Array to string conversion` markers.
- **HIGH — integer overflow → HTTP 500 on oversized `page`** — `app/Controllers/Blog.php`.
  - Evidence: `?page=9223372036854775807` → 500 `TypeError: BaseBuilder::limit(): offset must be ?int, float given`.
  - Fix: added `MAX_PAGE = 10000`; page is clamped with `min((int) $pageStr, MAX_PAGE)` and the normalize decision compares against `$pageStr` (string) rather than `(string)$pageRaw`, so out-of-range/array/scientific pages redirect instead of overflowing the `LIMIT` math.
  - Retest: `page=9223372036854775807`, `99999999999999999999`, `2147483647`, `1e309`, `-5`, `abc`, `[]`, `[a]` → 302 to `/blog?page=10000` (or 200 for in-range), no 500.

#### Findings — documented, NOT changed
- **LOW — search treats `%`/`_` as LIKE wildcards** — `app/Models/ArticleModel.php` `publishedFiltered()`.
  - Evidence: `?q=%`, `?q=%%`, `?q=___` each return all 19 published on page 1; `?q=%pag%` returns 4 (same as literal `pagi`). CI4 Query Builder `like()` binds the value (SQL-injection-safe) but leaves `%`/`_` as wildcards.
  - Impact: broader-than-expected match results only — still limited to `status='published'`, no injection, no data leak.
  - Recommendation: if literal search is required, escape `likeEscapeChar`/`%`/`_` in `q` before `like()` (left untouched to avoid fragile manual escaping that could double-escape backslashes under MySQLi `real_escape_string`).
- **INFO — `CI_ENVIRONMENT = development` leaks debug detail on error pages** — `.env`.
  - Evidence: any 404 (e.g. `/.env`) returns a JSON/HTML stack trace with absolute filesystem paths (`/Volumes/Data/Project/portal/system/CodeIgniter.php:1024`). Creditentials themselves are NOT exposed.
  - Recommendation: set `CI_ENVIRONMENT = production` for a production deployment. Left as-is because it is the owner's local dev configuration and debugging is intentional here.

#### Verified checks (passed)
- **Visibility (published-only).** Temporary draft (`status='draft'`, unique slug) inserted: `/blog/{slug}` → 404; absent from listing page 1 and page 2; search `q=<token>` → empty state with `"total": 0`; async `/blog/results?q=<token>` → `"total": 0`, empty state, no card. Draft's unique token only surfaced as the (escaped) reflected search term in the input value + filter chip, never in any article card or title. Temporary row deleted — 19 published / 19 total restored.
- **SQL injection.** No raw SQL; filter values flow through Query Builder `whereIn()`/`like()` binds and int-cast ids. `q=' OR 1=1--` → empty state "Tidak ada artikel", not "all articles"; `q=%27OR%201%3D1--` → 200 + empty state.
- **Output escaping.** `q=<script>alert(1)</script>` renders escaped (`&lt;script&gt;`, 0 raw `<script>` in body) in both the search input (`esc()`) and the active-filter chip (`esc()`); article title/excerpt/cover-alt/category/tag names all `esc()`'d in `blog_results.php`; JS updates use `region.innerHTML = data.html` (server-escaped markup) with totals via `textContent`, history via `data.canonicalUrl`, and query building via `encodeURIComponent`/`rawurlencode`.
- **Pagination.** 19 published with `PER_PAGE=10`: page 1 = 10 unique slugs, page 2 = 9 unique slugs, 19 distinct, 0 overlap; page 2 shows active page 2 + disabled "Berikutnya".
- **Sensitive files not downloadable.** `/.env`, `/app/Controllers/Blog.php`, `/app/Models/ArticleModel.php`, `/bc_config/portal_db_*.sql.gz`, and `/writable/logs/*.log` all return HTTP 404 (docroot is `public/`, not the repo root).
- **Secret hygiene.** `.env`, `bc_config/`, `writable/` are gitignored; the database password does not appear in any tracked file or in this PROGRESS.md.

#### Untested / owner to confirm
- Behaviour behind a separate WAF/reverse-proxy (none in this local setup).
- `CI_ENVIRONMENT = production` path (would require a config change the owner owns).

### Public Blog: async partial updates + canonical slug filter URLs (2026-11-07) — DONE, uncommitted
**Confirmed cause of the "slow/stuck" loading (measured before the fix):** every checkbox change called native `form.submit()` — a **full-page navigation**. Each navigation re-downloaded and re-parsed the whole document (58,087 bytes HTML, 34–58 ms server time on localhost, plus 9 script/link references to re-evaluate) and dropped all in-flight work, so rapid selections aborted each other mid-flight → stuck/slow feel. Server queries were NOT the bottleneck: `EXPLAIN` shows indexed lookups (`ref`/`eq_ref`, pivot PKs), filtered query ~2 ms.

**Implementation:**
- [x] `app/Config/Routes.php` — new `GET blog/results → Blog::results` registered BEFORE `blog/(:segment)` (else "results" parses as an article slug).
- [x] `app/Controllers/Blog.php` — rewritten: one filter parser (`parseFilterRequest()`) + ONE canonical URL builder (`blogUrl()`); new `results()` JSON endpoint (`{html, canonicalUrl, total, page, totalPages}`, `Cache-Control: no-store`, non-XHR → 302 to `/blog`); legacy/array/un-normalized document requests → 302 to the canonical URL (the redirect IS the normalization); shared `listingData()` powers both page and endpoint (public visibility rules everywhere, DISTINCT total, page clamped).
- [x] Canonical URL format: `/blog?categories=kesehatan,kebugaran&tags=kardio,kesehatan-mental&q=…&page=2` — slugs deduped + sorted ascending, each slug `rawurlencode`d, `q` only when non-empty, `page` only when > 1. Numeric tokens in incoming URLs are mapped via id → slug; `categories[]`/`tags[]` arrays (no-JS form fallback) and legacy `category=`/`tag=` accepted then normalized.
- [x] Validation caps: q ≤ 100 chars, ≤ 20 selections/group, token ≤ 100 chars, page clamped ≥ 1; unknown slugs dropped silently (no error, no loop — canonical-shaped URLs in any slug order are accepted without redirect, only dupes/legacy/array/malformed-page get 302'd). All lookups parameterized (`whereIn`); all reflected text `esc()`'d (verified `<script>alert(1)</script>` renders escaped).
- [x] `app/Views/portal/blog_results.php` — NEW partial (active-filter chips + cards/empty state + pagination) rendered by BOTH the full page (inside `#blog-results-region`) and the results endpoint — identical markup from one code path; every link built by `$blogUrl` and carries `data-blog-link` for async interception.
- [x] `app/Views/portal/blog_list.php` — checkbox name/value switched to slugs (`categories[]`/`tags[]` value=slug); shell now hosts only navbar/sidebar/loading-overlay/error-box; JS engine replaced: 250 ms debounce on checkbox changes (5 clicks in 8 ms → ONE request; 10-toggle burst in 1 ms → ONE request), AbortController cancels superseded requests, sequence counter so only the newest request may touch results/pagination/chips/URL and only the newest clears the loading overlay, timeout read per-request from `data-timeout` (default 8000), inline recoverable error ("Gagal memuat hasil… + Coba lagi") on failure/timeout, delegated listeners attached once at startup (9 link clicks after many content replacements → exactly 9 requests, no handler duplication), sidebar never disabled (overlay covers only the results column), `popstate` restores checkbox/q state and re-fetches without pushing history, pagination/chip/reset links intercepted for async updates with correct modifier-key/new-tab behavior, `pageshow` clears loading after Back/Forward cache restores.
- [x] `public/assets/portal/css/custom.css` — `.blog-error` styles (light + dark variants), retry button.

**Measured timings (localhost, curl):** full document `/blog?…` 33.7–57.6 ms, 58,087 bytes; JSON `/blog/results?…` 25.0–27.2 ms, 6,537 bytes (8.9× smaller payload, no CSS/JS/images re-fetched or re-parsed). In-browser: results fetch dur ≈ 77 ms / 6.3 KB transfer; `performance.getEntriesByType('navigation')` stays at exactly ONE entry (the initial load) — filter changes trigger zero document navigations.

**Verified with real browser interactions (2026-11-07, current DB data):**
- [x] Rapid selection: 5 checkboxes clicked in 8 ms → 1 debounced request; 10-toggle burst in 1 ms → 1 request; final results/chips/URL/checkboxes always match the LATEST selection (cat{kebugaran,kesehatan,teknologi}+tag{kardio,keamanan-digital} → 5, matches SQL).
- [x] Cancelled requests: with a slow in-flight request, the next change aborts it (logged `aborted: true`, `AbortError` swallowed silently, no error box) and only the newer request updates the UI and clears loading; a delayed older response cannot overwrite newer results (seq guard verified with artificial 800 ms delay).
- [x] Failed request (simulated network error): loading cleared, inline error + "Coba lagi" shown, URL unchanged, sidebar still interactive; retry re-applies the latest selection successfully.
- [x] Timeout: `data-timeout="500"` override → request aborted at ~524 ms → error shown, loading cleared, URL unchanged; retry succeeds. (Found+fixed during verification: timeout is now read per-request so the override works at runtime.)
- [x] Filter matching preserved (ANY/ANY/AND) and re-derived from CURRENT data (tag↔article assignment changed since morning: kardio now on articles 17–21): cat{kesehatan,kebugaran}+tag{kardio,kesehatan-mental}=5, cat{teknologi}+tag{kardio}=0 → async empty state "Tidak ada artikel yang sesuai." with Reset, cat{gym,teknologi}=9, tag{kardio,keamanan-digital}=10, cat{gym,kesehatan,teknologi}=14 → pages 10+4, all matching SQL.
- [x] Pagination: async page clicks (URL gains `&page=2`, active page highlighted), page reset to 1 on every filter/search change (page param omitted), pagination links carry full canonical filter state.
- [x] Chips: per-item × removal async and precise (removing search chip keeps categories+tags; removing one category keeps the rest+tags+q; sidebar checkbox unchecks in sync), Reset filter (sidebar + chips + empty state) → clean `/blog` in one async step.
- [x] Search: submit (button/Enter) fetches async, keeps all checkbox selections, resets to page 1; q with spaces/&/= round-trips encoded (`q=pagi%20siang%26x%3D1`); q > 100 chars truncated.
- [x] State: reload of `/blog?categories=gym,kesehatan,teknologi&page=2` restores page 2 + checked boxes; browser Back (popstate) → page 1 same filters; Forward → page 2 again; legacy `/blog?category=7` → 302 → `/blog?categories=teknologi` with the checkbox pre-checked; legacy `/blog?tag[]=4&category[]=8` → 302 → `/blog?categories=gaya-hidup&tags=kardio`.
- [x] Normalization: dupes (`kebugaran,kesehatan,kebugaran`) → 302 to deduped+sorted; `page=abc` → 302 dropping page; unknown slug mixed with valid → 200, silently filtered by valid ones only; 25 duplicates → 1 canonical slug.
- [x] Zero console errors across the whole session; mobile 390 px re-verified (no horizontal overflow, sidebar stacks, region + error box present); glass panel styles intact after cache-bust.

### Public Blog sidebar: multi-select glass filters (2026-11-07) — DONE, uncommitted
**Implementation (reuses the functional sidebar from the same day):**
- [x] `app/Models/ArticleModel.php` — `publishedFiltered()` now takes `category_ids[]`/`tag_ids[]` sets: ANY id within a group (parameterized `whereIn`), groups + search combined with AND, empty group = no restriction; page uses GROUP BY `a.id` and the total uses `COUNT(DISTINCT a.id)` so multi-category articles (e.g. article 4 = categories 3,4,5) are returned and counted exactly once.
- [x] `app/Controllers/Blog.php` — normalizes `category[]`/`tag[]` (multi) AND legacy `category=`/`tag=` single values through one path; invalid ids silently dropped after a parameterized existence check; selected rows passed for chips.
- [x] `app/Views/portal/blog_list.php` — ONE GET form wraps the three glass panels (Pencarian / Kategori / Tag); categories and tags are vertical checkbox lists with clickable labels, right-aligned count badges, bounded 220px scrollable lists, `overflow-wrap:anywhere` labels; checkbox change auto-submits (page param omitted → back to page 1); "Reset filter" link in the sidebar + active-filter chips row (per-item × removal) + reset inside the empty state; loading overlay (`blog-loading`) shown on submit, cleared on `pageshow` (Back/Forward). Pagination links carry every `category[]`/`tag[]`/`q` in the same `category[]=` shape the form produces. Full navigation instead of fetch means no stale-response race is possible by construction (the spec's async guards are therefore not needed; visible loading state is still provided).
- [x] `public/assets/portal/css/custom.css` — glass panels: `rgba(255,255,255,0.62)` + `backdrop-filter: blur(10px) saturate(1.15)`, thin light borders, 14px radius, soft light shadows, inset gloss; `@supports not (backdrop-filter)` opaque `#fff` fallback; `@media (prefers-color-scheme: dark)` dark-glass variant with light borders/text; keyboard focus ring (`:focus-visible` outline) and checked state clearly visible; mobile paddings + no horizontal overflow.

**Verified with real browser interactions (2026-11-07):**
- [x] Sidebar structure: 3 separate panels (Pencarian/Kategori/Tag), glass computed styles confirmed (`rgba(255,255,255,0.62)`, `blur(10px) saturate(1.15)`, radius 14px), badges right-aligned, 5 category + 10 tag checkboxes.
- [x] Multi-category: Fitness 2 + Teknologi → 9 cards, both checkboxes stay checked, URL `?category[]=3&category[]=7`, chips per category; unchecking Fitness 2 → 5 cards, URL updated to `?category[]=7`.
- [x] Multi-tag: Kardio + Keamanan Digital (with Teknologi category) → 5 cards (`category[]=7&tag[]=4&tag[]=13`), AND across groups.
- [x] Combined: + search "data" → exactly 1 card ("Data Pribadi di Internet…"), q + all checkboxes preserved; active-filter row shows 4 chips.
- [x] Empty result: Teknologi ∩ Kardio = 0 → "Tidak ada artikel yang sesuai." + Reset; chip × removes one id only (tag 13 removed, tag 4 stays checked).
- [x] Reset filter → clean `/blog`, 10 cards, zero selections, zero chips.
- [x] Pagination with filters (categories 3+5+7 = 14 articles): page 1 = 10, page 2 = 4, page links carry all `category[]` params, active page highlighted, checkboxes stay checked after navigation; **14 cards / 14 unique / 0 duplicates across pages**; article 4 (3 categories) appears exactly once.
- [x] Reload of a filtered paginated URL restores the same 4 cards + checked boxes; browser Back returns to page 1 with the same filter selections (state lives in the URL).
- [x] Legacy URLs still work AND pre-select the matching checkboxes: `/blog?category=7` → 5 cards; `/blog?tag=4` → 5 cards.
- [x] Loading overlay shows on checkbox change (verified synchronously before navigation) and clears on `pageshow`.
- [x] Mobile 390px (real iframe viewport): no horizontal overflow (`scrollWidth 390`), cards 1 column full width, sidebar stacks below the articles, search input + Cari button on one row, checkbox rows fit.
- [x] Dark mode: rules present in the stylesheet (dark media query + `@supports` fallback verified in `cssRules`); simulated dark render shows readable contrast (`rgba(31,31,31,0.60)` panel, `#e6e6e6` labels, light badges). Light-mode label/badge colors verified via computed styles.
- [x] Server-side cross-check: SQL counts match every filter combination tested (9 / 5 / 10 / 5 / 3 / 14 / 0).
- [ ] Owner: real keyboard Tab pass over the checkboxes to feel the focus ring (rule verified in CSS; programmatic focus cannot trigger `:focus-visible`).

### Public Blog experience: navigation, layout, functional sidebar, pagination, samples (2026-11-07) — DONE, uncommitted
- [x] `app/Helpers/menu_helper.php` — the main BLOG menu entry (menu row is `section/blog`) now opens the full listing `/blog` instead of the homepage `#blog` anchor; other section anchors unchanged.
- [x] `app/Models/ArticleModel.php` — `publishedFiltered()` (single category/tag + search), `publishedCategoryCounts()` / `publishedTagCounts()` (DISTINCT published counts for the sidebar), `publishedPaginated()` retained; order `published_at DESC, id DESC` everywhere.
- [x] `app/Controllers/Blog.php` — server-side pagination at 10/page with DISTINCT total and last-page clamp; filters parsed/validated; sidebar data computed; `card_layout` still read from Pengaturan Blog.
- [x] `app/Views/portal/blog_list.php` — 75%/25% two-column layout (`col-lg-9` cards / `col-lg-3` sidebar), Portal-theme pagination (Sebelumnya, 1, …, nearby pages, last, Berikutnya; active `#333` badge, disabled ends, hidden when 1 page), every page a reloadable URL, empty state, active-filter chips.
- [x] `public/assets/portal/css/custom.css` — theme-color pagination (`#333` active, `#f2f3f5` hover), card readability fixes (left-aligned meta, `margin-top:0`, `overflow-wrap:anywhere`), sidebar panels.
- [x] Sample content: the 15 requested Indonesian samples (5× Kesehatan/Teknologi/Gaya Hidup, tags wired) already existed from `articles:seed-topic-samples`; re-run skips all 15 (idempotent). 19 published total; owner article 4 kept as-is (owner later added categories 3,4,5 to it themselves).
- [x] Verified: BLOG nav click lands on `/blog`; page 1 = 10, page 2 = 9, 19 unique / 0 repeats; sort `published_at DESC, id DESC` (equal dates ordered by id desc); single filters (Teknologi 5, kardio 5, "peregangan" 1), combined filter 1 result, unknown ids ignored, empty state for no matches; 2- and 3-column settings inside the 75% area (3-col: rows 3+3+3+1, 10th card new row same width); mobile 1 column; original setting restored to `grid_2_columns` via the admin form (DB row byte-identical: `card_layout`/`layout` = `grid_2_columns`, limit 3).

### Sample articles seeded; database was reset externally (2026-11-06)
- [x] **Important discovery:** `portal_db` was wiped and re-seeded externally between sessions — ALL owner articles (1, 2, 3, 10, 12), pages, article_tags, and older media rows are gone; `admins` table replaced by `admin_users` (10 migrations applied). Code/docs from earlier milestones are unaffected; only their DB references are gone. Nothing was deleted by us.
- [x] Created 4 sample articles via a temporary idempotent spark command (`app/Commands/SeedSampleArticles.php`, untracked, safe to delete): uses the editor's exact persistence path (`ArticleModel::insert` + `syncTags` + shared `SanitizesHtml`). Published: id 1 `menata-meja-kerja-dalam-15-menit` (154 kata), id 2 `akhir-pekan-di-subang-jalan-pagi-dan-berburu-foto` (164 kata), id 3 `cara-membuat-cadangan-foto-keluarga` (159 kata) — all `2026-11-06`, category 3, tag 2 on 1 & 2. Draft: id 4 `catatan-uji-editor-gambar-dan-video-youtube` (161 kata) with media-library image (media #22) at 25% `img-align-center`, short caption, one deliberate `<p>&nbsp;</p>`, then the project's dQw4w9WgXcQ responsive embed.
- [x] Verified: 3 published return 200 on `/blog/…` and appear on the homepage; draft is 404 public, 302 on unauthenticated saved-preview, visible in admin list/edit; re-running the command skips all four slugs (idempotent).

### Inline image sizing: corner-handle drag normalized to % (2026-11-06 → 2026-11-07) — DONE
- Investigation recap: sanitizer keeps img `style` (width/max-width/height allowed); preview and DB faithfully preserve whatever the editor serializes; proportions of the 25% preset match at every hop; public CSS has no width-forcing rules. Confirmed container mismatch (editor body ~778px at 888px viewport vs public column 696px) — percentages keep proportion, absolute sizes drift; that mismatch is by design and fine.

**Root cause (measured with a real trusted-pointer drag):** TinyMCE corner-handle drag-resize leaves the image in absolute pixels (`width: 288px; height: 383px` on the live style AND a px `data-mce-style` snapshot). Pixels only match the editor column, so the same image takes a different share of the narrower public column: **37.0% in the editor → 41.4% in preview/DB-driven pages (+4.4pp)**. The drift is born at drag time; serialization, sanitizer, preview and DB pass it through unchanged.

**Fix (uncommitted):**
- [x] `public/assets/admin/js/editor-controls.js` — `portalEditorSetup` registers an `ObjectResized` handler: on the NEXT TICK (TinyMCE writes its final px styles after the last `ObjectResized` fires, so a synchronous handler was overwritten), convert the dragged width to a percentage of the editor body width (1 decimal, capped at 100%), set `height: auto` (aspect ratio preserved), remove `width`/`height` attributes, and sync the `data-mce-style` snapshot to the new style — the serializer prefers the snapshot over the live attribute, and without this sync the saved HTML silently reverted to px. Output format matches the resize presets exactly (`width: N%; max-width: 100%; height: auto`); alignment classes and `max-width: 100%` are untouched. Applies to article AND page editors (shared setup).
- [x] `article_form.php` + `page_form.php`: `editor-controls.js` script tag bumped to `?v=20261107-3`.

**Verified with a REAL trusted pointer drag (target 37%, draft article 4, image reset to the 25% preset format before the run):**
- [x] Editor after drag: 288px / 778px = **37.0%**, aspect 0.751, `img-fluid img-align-center` kept; drag log captured trusted `handle-mousedown` + `ObjectResized` events.
- [x] Serialized textarea output: `style="width: 37%; max-width: 100%; height: auto"` → 37.0% of the editor column.
- [x] Unsaved preview (`POST preview-unsaved`, 696px public column): 258px = **37.0%** (pre-fix the identical drag measured 41.4%).
- [x] Real save (requestSubmit → pre-save hook): DB body keeps `width: 37%; max-width: 100%; height: auto` with no width/height attributes; reopened editor: 288/778 = **37.0%**.
- [x] Drift at every hop: **0.0pp** (tolerance 1pp).

**Browser-harness notes for future drag runs (cost hours to learn):** the drag tool presses at coordinates computed BEFORE it re-scrolls the page, so lock page scroll first (`body{position:fixed; top:-scrollY}` — viewport positions are preserved and its internal scrollIntoView becomes a no-op; always detox a stale lock at the start of the next run); the TinyMCE content iframe is only ~245px tall (content scrolls internally) and the SW handle often sits at/below the iframe's bottom edge — scroll the iframe's INTERNAL content up (`doc.scrollTop += need`) until the handle is visible, then target a point ~3px above the iframe's bottom edge; drag markers must be `pointer-events:none` plus `role=button`+`aria-label` to let input pass through while staying visible to a11y snapshots.

**Data hygiene:** draft article 4's original body was captured before testing and restored byte-identical afterwards (`body_len` 1939, MD5 `d201962891c7e7822a0d1cf359c719ab`), `status='draft'` re-verified, `published_at` reset to NULL (the save flow's date input defaults to today for drafts and had refilled it — restore included it).

**Requires owner manual verification:**
- [ ] One drag with a real mouse/trackpad on any article image to confirm the feel and the final percentage (the automated run used trusted CDP pointer input — real input events at programmed coordinates; a human drag is the last proof).
- [ ] Visual check of a dragged image in light AND dark themes.

### Article spacing consistency: editor ↔ preview ↔ public (2026-11-06)

**Root cause (measured in a real browser, not assumed):**
1. **TinyMCE serializer collapses blank lines.** A blank line the owner types (Enter-Enter) lives in the editor DOM as `<p><br></p>`; TinyMCE's serializer outputs it (and `<p></p>`) as an EMPTY `<p></p>`. On the public page an empty `<p>` renders with **0 px height** (margin-collapse eats it), while `<p>&nbsp;</p>` renders a real 25.32 px line — so every intentional blank line silently vanished in Preview/public while staying visible in the editor.
2. **`insertContent` consumed the blank paragraph under the caret.** Inserting a video (block content) with the caret inside an empty `<p>` physically REPLACED that paragraph in the editor DOM — the blank line was deleted at insert time, before saving ever happened.
3. **Editor typography diverged from public.** Editor body was 16 px/1.4 vs public `.article-body` 14 px/1.808 with `p { margin-bottom: 16px }` — even identical markup looked different. (The sanitizer was exonerated: `<p>&nbsp;</p>` passes through `sanitizeHtml()` unchanged.)

**Fix (all uncommitted):**
- [x] `public/assets/admin/js/editor-controls.js` — `portalEditorSetup` now registers a `p` **serializer node filter on `PreInit`** (inside `setup`, `editor.serializer` does not exist yet — the first attempt threw `TypeError … 'addNodeFilter'`; PreInit is the correct hook). In the OUTPUT HTML only: effectively-empty paragraphs (whitespace/nbsp text or `<br>`-only children) are filled with `\u00a0`; paragraphs carrying TinyMCE's own `data-mce-placeholder` are dropped from output instead, so editor filler never leaks. Detection walks the direct child chain (`node.firstChild`/`next`) — an earlier attempt used `node.walk(cb)` whose early-return contract emptied ALL paragraphs including text ones; caught in testing and replaced.
- [x] `article_form.php` YouTube dialog `onSubmit` — before `editor.insertContent`, if `editor.selection.getNode()` is an empty `<p>` (no img/iframe/table/list, no real text), it is filled with `&nbsp;` and re-selected, so the blank line under the caret survives block insertion.
- [x] `content_style` in **both** `article_form.php` and `page_form.php`: `body { font-size: 14px; line-height: 1.8 }` + `p { margin: 0 0 1rem }` to match public article typography exactly.
- [x] **Data-loss bug found and fixed on the way:** the pre-save submit hook called native `form.submit()`, which BYPASSES TinyMCE's own form-submit sync — the textarea could still hold stale content and silently revert recent edits. The hook now runs `tinymce.triggerSave()` before the CSRF refresh + native submit.
- [x] Both forms' `editor-controls.js` script tags got `?v=20261106` cache-busting (the browser was serving a stale cached copy during verification).

**Verified with real browser interactions (authenticated, 2026-11-06):**
- [x] Serialization matrix (article editor): text+1 blank → `<p>&nbsp;</p>`; text+2 blanks → two nbsp paragraphs; existing `<p>&nbsp;</p>` untouched; `<br>` inside text and Shift+Enter soft breaks preserved; `data-mce-placeholder` paragraphs dropped from output; paragraph with nested `<strong>` counted as content; live editor DOM never modified by the filter; zero console errors.
- [x] Real YouTube dialog flow with the caret inside a blank paragraph (toolbar click → URL → Sematkan): dialog closes, success notification shows, DOM keeps `P(text) → P(&nbsp;) → video → P(&nbsp;)`, serialized output keeps the blank line before the video.
- [x] End-to-end on a throwaway published article (`uji-spasi-20261106`): text → 1 blank → video → 2 blanks → text → image (50 %, centered) → 1 blank → video → 1 blank → text.
  - Editor (iframe, after content_style): gaps **57 / 92 / 16 / 57 / 51 px**, typography 14 px / 25.2 px / 16 px margin.
  - Unsaved preview (`POST preview-unsaved`): gaps **57 / 93 / 16 / 57 / 51 px**, identical typography, 5 nbsp paragraphs, no `data-mce-*`/`contenteditable` in the rendered body.
  - Saved &amp; reopened: DB body has exactly five `<p>&nbsp;</p>`, wrapper `style` and image `style`/`class` intact, zero editor-only attributes.
  - Public page: gaps **57 / 93 / 16 / 57 / 51 px**, same typography — visually identical to the editor; no horizontal overflow.
  - One blank (57 px) vs two blanks (93 px) vs no blank (16 px) remain clearly distinguishable — no fixed video margin was used.
- [x] Narrow/mobile simulation (375 px content column): identical gaps, video wrapper responsive (56.25 % padding-bottom trick), no horizontal overflow.
- [x] Regression smoke on the fixture's edit page: reopen shows all 5 nbsp blanks intact; image align-center applies `img-align-center`; resize-25 % sets `width 25 %`; fullscreen toggles on/off; `Hapus Video` via the REAL selection flow (iframe `mousedown` → enabled button → click) removes only the selected embed and keeps surrounding blanks; serialized HTML stays free of `contenteditable`/`data-mce-*`.
- [x] Real save exercised the full production path (`requestSubmit` → pre-save hook → `triggerSave()` → fresh CSRF → native submit) — article stored correctly.
- [x] Pages editor (`page_form.php`): same serializer filter + typography verified (blank preserved, 14 px/25.2 px/16 px, zero console errors).
- [x] Owner data untouched: article 10 (`uji-gambar-video`) DB content byte-identical to before (its historical collapsed `<p></p>` lines are owner data — NOT rewritten, per constraint); media #48 untouched; throwaway fixture deleted (articles row + `article_tags` check, zero leftovers).

**Requires owner manual verification:**
- [ ] Real keyboard Enter-Enter blank lines (synthetic keystrokes cannot reach the TinyMCE iframe from this harness; DOM/API-equivalent states were used instead — the serializer treats them identically, but a human keypress test is the final proof).
- [ ] Visual comfort judgement of the new 57 px / 93 px rhythm in light AND dark themes (spacing is theme-independent, but the call is aesthetic).
- [ ] Crop dialog (canvas flow untouched by this change; only code-level review — no browser run this session).
- [ ] Existing owner articles still show collapsed blank lines (by design — old content is not rewritten); owner can re-type the blank lines in any article where they matter.

### Unsaved-content article preview (2026-11-06)

**Cause (confirmed by inspection):** the form's Preview button was a plain `GET admin/articles/preview/(:num)` link that rendered the article straight from the database — unsaved editor/form changes never reached the server, so Preview always showed the previously saved content until the article was saved.

**Fix (uncommitted, preserves the YouTube deletion fix):**
- [x] New `POST admin/articles/preview-unsaved` (`Articles::previewUnsaved()`, adminAuth + CSRF protected): renders the CURRENT form content through the public `blog_detail` view. Nothing is written — no insert/update, no status change, DB untouched.
- [x] Same sanitization and rendering rules as saved articles: the posted body goes through the exact `sanitizeHtml()` used by `save()`; category/tags/related resolved the same way.
- [x] Payload covers title, slug (auto-generated when blank), excerpt, body, cover selection (library radio / current cover), cover alt, status, published date, and detail layout (sidebar/full).
- [x] Form Preview button now: `tinymce.triggerSave()` (editor → textarea sync) → submits the live form to the preview endpoint in a NEW tab → restores action/target immediately. `form.submit()` is used deliberately to skip HTML5 validation, so incomplete forms can still be previewed. The original tab never navigates — unsaved content, selection and cursor are preserved when preview opens or closes.
- [x] Isolation & staleness: the preview payload lives only in the POST request (no server-side temp storage), so previews are isolated per tab/article by construction; both preview endpoints send `Cache-Control: no-store` (verified via curl) so no stale cached preview can appear.
- [x] Banner label: preview pages show "Pratinjau admin" plus, for unsaved previews, "Pratinjau perubahan belum disimpan — perubahan pada form belum masuk ke database…". Saved-GET preview keeps only the original banner.
- [x] CSRF chain preserved: because the app rotates the CSRF token on every POST (`regenerate = true`), a preview POST used to stale the form's token. Added `GET admin/articles/csrf-refresh` (returns the current hash; GET does not rotate) plus a pre-save submit hook that refreshes the token right before a real save — verified: save succeeds right after preview. A best-effort delayed refresh also updates the header Logout form after previews.
- [x] Preview button is now available on the create form too (previously only on edit).
- [x] Saved-article preview from the article list (`GET preview/(:num)`) unchanged and still working (verified: banner without the unsaved label, correct saved content).

**Verified with real browser interactions (authenticated):**
- [x] Existing article 10 with saved video A (`dQw4w9WgXcQ`): deleted A and inserted B via the real YouTube dialog without saving → Preview showed B, not A, with the unsaved banner; form tab stayed on `/admin/articles/edit/10` with content intact.
- [x] Added a second video C (youtu.be URL) without saving → Preview showed both B and C.
- [x] Removed video C without saving → Preview showed only B (C omitted).
- [x] Database unchanged throughout: article 10 row still contains only video A; public `/blog/uji-gambar-video` still renders video A with `pointer-events: auto`.
- [x] Unsaved text, cover-image selection (library radio), inline image addition and removal, and sidebar layout each reflected immediately in the next Preview.
- [x] Save-and-reopen persistence verified on a throwaway article (created, previewed unsaved, saved — the save succeeded right after a preview, exercising the CSRF pre-save hook — reopened: title/text/video persisted; saved-GET preview correct). Fixture deleted afterwards; owner media untouched (media #48 uploaded today at 11:00 predates/parallels testing and belongs to the owner's manual checks — left as is).
- [x] Security: POST `preview-unsaved` returns 403 without a valid CSRF token and 403 when not logged in; zero console errors during all tests.

**Requires owner manual verification:**
- [ ] Visual check of the unsaved-preview banner wording/placement on the real theme (light/dark).
- [ ] Previewing a cover image freshly uploaded via the file input but NOT yet saved: the upload lives only on the client, so Preview falls back to the library selection/current cover; the new file appears after saving (known limitation, by design).
- [ ] Owner's usual browser (tab behavior: each Preview opens a new tab; original form tab never navigates).

The earlier YouTube selection/deletion + fullscreen/resize milestone (2026-11-05 section below) is untouched by this change.

### YouTube selection/deletion + fullscreen/resize editor (2026-11-05)

**Implementation (uncommitted):**
- [x] New shared `public/assets/admin/js/editor-controls.js`: `window.portalEditorSetup` (fullscreen) + `window.portalVideoSetup` (video selection/deletion), wired into both `article_form.php` and `page_form.php`.
- [x] `Hapus Video` toolbar button (article editor): active only while a `.video-embed-wrapper` is selected; removes the whole wrapper (iframe included) without touching surrounding text or other media; caret is placed in the adjacent paragraph (a new `<p>` is created if none usable — a following video wrapper is never used as the caret target).
- [x] Wrapper selectable as one unit: parser filter sets `contenteditable="false"`/`data-mce-contenteditable="false"` on `.video-embed-wrapper` (applies to saved and newly inserted embeds, including the YouTube dialog path); serializer strips `contenteditable` again so saved HTML stays clean; `[data-mce-selected]` shows a blue outline.
- [x] Iframe no longer traps editor clicks: `.video-embed-wrapper iframe { pointer-events: none; }` in `content_style` (editor only; playback unaffected on public pages where `pointer-events` stays `auto`).
- [x] Delete/Backspace delete the selected embed; intercepted only when a wrapper is selected (no modifier keys), so normal text editing is untouched. Removal is wrapped in `editor.undoManager.transact()`.
- [x] Fullscreen via bundled local TinyMCE `fullscreen` plugin — no paid plugins/external keys: `Layar Penuh` toolbar button on article and page editors whose label toggles to `Keluar Layar Penuh` (via `FullscreenStateChanged`).
- [x] Default sizes preserved: article editor `height: 480` (+ `resize: true`, `min_height: 320`), page editor `height: 400` (+ `resize: true`) — vertical drag handle labelled "Ubah ukuran" in the status bar.
- [x] Stacking fixed with `.tox.tox-tinymce.tox-fullscreen { z-index: 1100; }` and `.tox.tox-tinymce-aux { z-index: 1200; }` in both forms.
- [x] Crop dialog viewport fix: crop image/container capped at `calc(90vh - 180px)` so `Terapkan`/`Batal` stay visible inside the viewport in fullscreen (previously the buttons were pushed below the fold on short screens).

**Verified with real browser interactions (authenticated admin session, 2026-11-05):**
- [x] Article editor loads with zero console errors; default height 480 px, `fullscreen` plugin local, both buttons present, resize handle present.
- [x] Wrapper `contenteditable="false"`, iframe computed `pointer-events: none`; real mousedown on the wrapper selects it; `Hapus Video` disabled without selection, enabled with selection.
- [x] Of two embeds: toolbar click removed only the selected embed, kept the other embed and all three paragraphs, caret landed in a `P`, serialized HTML free of `contenteditable`/`data-mce` attributes.
- [x] Real `Delete` and real `Backspace` keypresses (browser-level, not synthetic events) each removed the selected embed, kept the other embed and all text, caret in a `P`.
- [x] Undo restores the deleted embed; Redo removes it again (verified deterministically after content reset).
- [x] Insertion through the real "YouTube" dialog (watch URL) works; dialog-inserted embed is also selectable (`contenteditable="false"` applied) and deletable via real `Delete`; Undo restores it.
- [x] Existing saved embeds: created test article with 2 embeds (published), reopened in the editor, both selectable; removed one via `Hapus Video`, saved, reopened — removal persisted in DB (only the second embed remains, all paragraphs intact, no editor attributes).
- [x] Public article page after deletion: removed video gone, remaining embed present, all paragraphs rendered, iframe `pointer-events: auto` (playback intact).
- [x] Normal text editing unaffected: real Backspace/Delete on plain text work character-by-character while no embed is selected.
- [x] Fullscreen (article + page editors): expands to the full viewport (covers it, z-index 1100 above sidebar 1038 and header 1034 — no overlap), toolbar visible, label toggles to `Keluar Layar Penuh`; unsaved content, selection, and caret position preserved on enter and exit; exit restores the exact default height (480/400).
- [x] In fullscreen: YouTube dialog, "Pilih dari Pustaka" dialog (34 thumbnails), image insertion from library, resize 50%, align-center, and the crop dialog all usable and correctly stacked (aux z-index 1201 above the editor; crop overlay z-index 99999 above all).
- [x] Real crop in fullscreen (drag selection + `Terapkan`): new cropped file created, image `src`/`data-mce-src` updated, success notification, editor stays in fullscreen; working on the fixed dialog (buttons in viewport).

**Requires owner manual verification (not exercisable by automated tooling):**
- [ ] Vertical resize by actually dragging the "Ubah ukuran" handle at the editor's bottom-right (handle exists and `resize: true`/`min_height: 320` are active; synthetic mouse/pointer events cannot drive TinyMCE's internal drag helper, so the drag gesture itself was not automated).
- [ ] Visual comfort of the selection outline and fullscreen behavior on the owner's real screen size/dark-mode theme.
- [ ] Actual YouTube playback after clicking through preview/public (iframe loads on the public page; playback itself requires internet + an embeddable video).
- [ ] The drag interaction inside the crop dialog (selection rectangle) on a real pointer device — apply/cancel flow was verified programmatically.

Test fixtures used for verification (test article + cropped media file) were removed afterwards; owner content untouched (5 articles incl. owner's own test draft, 3 pages, 3 tags, 1 category intact). No commit/push performed.


### Public navigation: URL hash + active item + scroll offset consistency (2026-11-07) — DONE, uncommitted

**Root cause:** `custom.js` bound section-link clicks with `e.preventDefault()` and jQuery smooth-scroll but never wrote `location.hash`; Bootstrap 5 ScrollSpy only toggled `.active`, never the URL. `bootstrap.Collapse.getOrCreateInstance(collapse)` with default `toggle: true` also auto-showed a closed mobile menu when a nav link was clicked.

**Implementation:**
- [x] `public/assets/portal/js/custom.js` — single-source nav block:
  - `HEADER_OFFSET` measured from the live `#mainNav` height (≈68 px), used for both scroll target and ScrollSpy `offset`.
  - `scrollToHash()` drives every programmatic jump (link click, logo, back-to-top, Back/Forward, direct-hash load), offsets by `HEADER_OFFSET - 8 px` so headings clear the navbar, and uses `pushState` for clicks / `replaceState` for restore/manual-scroll.
  - Manual scroll mirrors ScrollSpy's active section to the URL via `replaceState` only after the user has actually scrolled (`hasScrolled`), so no history spam occurs.
  - `programmaticScroll` + `suppressingHash` flags prevent ScrollSpy from overwriting the URL mid-animation.
  - `setNavActive()` fallback guarantees the nav matches the target even when ScrollSpy's direction-aware IntersectionObserver skips a programmatic jump.
  - Logo (`#page-top`) and back-to-top both scroll to top and set `/#home` via `replaceState` (no extra history entry).
  - Mobile menu close now uses `bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).hide()` so a closed menu is never auto-shown.
- [x] `app/Helpers/menu_helper.php` — computes `active` flag server-side for blog/page items from `service('uri')->getPath()`; section items on the homepage remain driven by ScrollSpy.
- [x] `app/Views/portal/blog_list.php`, `blog_detail.php`, `page.php` — render `active` class on matching nav items; mobile menu close uses `{ toggle: false }`.
- [x] `app/Views/portal/home.php` — temporary cache-buster `?v=2026110705` on `custom.js` include to defeat browser cache during verification (to be reverted or kept).

**Verified with real browser interactions (headless Chromium, 888×804 viewport, 2026-11-07):**

| Label | Expected dest | Observed final URL | Active item | ScrollY (≈target) | Result |
|---|---|---|---|---|---|
| Home (fresh load) | `/` | `/` | Home | 0 | ✓ |
| About click | `/#about` | `/#about` | About Us | 533–690 | ✓ |
| Portfolio click | `/#portfolio` | `/#portfolio` | Portfolio | 1179–1217 | ✓ |
| Contact click | `/#contact` | `/#contact` | Contact Us | 4121–4234 | ✓ |
| Home click | `/#home` | `/#home` | Home | 0 | ✓ |
| Manual scroll to Portfolio | `/#portfolio` (replaceState) | `/#portfolio` | Portfolio | 1183 | ✓ |
| Manual scroll to About | `/#home` (replaceState) | `/#home` | Home | 525 | ✓ |
| Logo click | `/#home` | `/#home` | Home | 0 | ✓ |
| Back-to-top click | `/#home` | `/#home` | Home | 0 | ✓ |
| Back/Forward (click history) | prior/fwd hash | matches | correct | matches | ✓ |
| Direct load `/#about` | `/#about` | `/#about` | About Us | 533–690 | ✓ |
| Direct load `/#contact` | `/#contact` | `/#contact` | Contact Us | 4121–4221 | ✓ |
| Reload `/#about` | `/#about` | `/#about` | About Us | 533 | ✓ |
| Blog nav click from `/` | `/blog` | `/blog` | Blog | — | ✓ |
| Test1 nav click from `/` | `/pages/test` | `/pages/test` | Test1 | — | ✓ |
| Cross-page About from `/pages/test` | `/#about` | `/#about` | About Us | 533 | ✓ |
| Mobile menu open → About click | closes + `/#about` | closes | About Us | 533 | ✓ |

**Standalone-page server-side active checks (curl):**
- `/blog` → Blog active; `/blog/{slug}` → Blog active; `/pages/test` → Test1 active.

**Notes / caveats:**
- ScrollSpy's active boundary for the About section is slightly above its physical top (scrollY ≈ 525 for aboutTop ≈ 593 with HEADER_OFFSET 68), which is expected Bootstrap 5 behavior with the configured offset; the nav and URL remain consistent.
- The temporary `?v=2026110705` cache-buster should be removed before release if a long-term asset versioning scheme is preferred.

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

### Article Image Management & YouTube Embedding (Owner Accepted — Ready to Commit)

**Owner acceptance (2026-11-04):** manual checks for upload, library selection, image resize/alignment, crop, YouTube embed, and article save/reopen/public rendering all successful.

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
- [x] Article save wrapped in database transaction; failure rolls back article + tag changes and returns Indonesian error message without data corruption
- [x] No partial changes from failed save: `sanitizeHtml()` runs just before the transaction, so exception prevents any DB write

**Sanitizer attribute parsing (fixed 2026-11-04):**
- [x] Root cause: line 89 used `$attr[2] !== '' ? $attr[2] : ($attr[3] !== '' ? $attr[3] : ($attr[4] ?? ''))`; for double-quoted empty values (`alt=""`) groups 3 and 4 are omitted from the `PREG_SET_ORDER` array, producing `Undefined array key 3`
- [x] Fix: inspect the first character after `=` in the full match to determine which capture group holds the value (double quote → group 2, single quote → group 3, otherwise → group 4); empty quoted values are preserved
- [x] Regression checks pass for: empty double-quoted, double-quoted value, single-quoted value, unquoted value, boolean attribute, single-quoted empty, unquoted empty, style attribute, full article body
- [x] Security preserved: `<script>`, event handlers (`onerror`), evil iframe src, `javascript:` href all stripped; approved YouTube iframes and image resize/alignment classes/styles survive

**TinyMCE editor integration (article_form.php):**
- [x] Custom toolbar buttons: "Unggah Gambar" (file upload), "Pilih dari Pustaka" (media library grid dialog)
- [x] Image alignment buttons: left/center/right via CSS classes
- [x] Resize preset buttons: 25%, 50%, 75%, 100% of content width
- [x] "Potong" crop button with visual drag-select overlay dialog, Apply/Cancel, Indonesian coordinate display
- [x] "YouTube" embed button with URL input dialog, Indonesian help text about supported formats and internet requirement
- [x] Click to select image, double-click for alt text edit + remove dialog
- [x] CSRF token returned in every JSON response body via `withCsrf()` helper; `refreshCsrf()` reads token from response (cookie is HttpOnly, unreadable by JS); hidden form fields also updated so the main save form works after AJAX POSTs
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

**Sanitizer attribute-parsing fix verification (2026-11-04):**
- [x] PHP regression script: 13 cases covering empty double-quoted, double-quoted value, single-quoted value, unquoted value, boolean attribute, single-quoted empty, unquoted empty, style attribute, full article body, script stripping, event-handler stripping, evil iframe stripping, and `javascript:` href stripping — all pass with zero warnings
- [x] Actual article save via browser: formatted text (`<strong>`, `<em>`), inline cropped image with 50% width + center alignment, and approved YouTube embed — saved, reopened, and public-rendered correctly
- [x] Public page `/blog/artikel-uji-simpan` rendered the cropped image, alignment, width, YouTube iframe, and formatted text before test cleanup
- [x] Failed save left no partial changes: `articles` and `article_tags` tables unchanged for existing articles; transaction added to prevent future partial writes
- [x] PHP lint clean on `app/Controllers/Admin/Traits/SanitizesHtml.php` and `app/Controllers/Admin/Articles.php`

**Save/reopen persistence:**
- [x] Article with inline image (class, style, alt) + YouTube embed saved → HTTP 303 redirect
- [x] Reopened in edit form → all image settings and YouTube embed preserved in textarea
- [x] Public article page renders all alignment CSS, video wrapper, and responsive styles

**Crop end-to-end browser test (2026-11-04):**
- [x] Crop dialog opens on image click → "Potong" button → visual overlay with drag-select
- [x] Mouse drag creates 200×150 selection → "Terapkan" clicked → fetch POST returns HTTP 200
- [x] Response includes `{location: "...cropped...", csrf_test_name: "...new_token..."}`
- [x] Dialog closes, TinyMCE image src updated (both `src` and `data-mce-src`), success notification "Gambar berhasil dipotong."
- [x] Width (50%), alignment class (img-align-center), and alt text ("Gambar uji") preserved after crop
- [x] Hidden CSRF form field updated by `refreshCsrf()` — main form save works after AJAX POSTs
- [x] Article saved (redirect to list) → reopened → editor shows cropped image URL
- [x] Public page at `/blog/uji-gambar-video` renders cropped image with correct alignment and width
- [x] Cropped file dimensions: 200×150 (from 400×300 source, crop area 200×150) — verified via `getimagesize()`
- [x] Original media file preserved (not deleted or modified by crop)

**Regression (no breakage):**
- [x] Homepage → 200; Blog list → 200; Existing article detail → 200; Page → 200
- [x] Admin articles/pages/menu → 200; Preview → 200
- [x] Data intact: 3 pages, 7 menu items, 4 articles (3 original + 1 test), 1 category, 3 tags

**Requires owner manual testing (visual interactions not fully exercisable via automated tooling):**
- [ ] Click "Unggah Gambar" button in editor toolbar → file picker opens → image inserts at cursor *(automated upload endpoint verified; owner confirmed manual insertion works)*
- [ ] Click "Pilih dari Pustaka" button → media grid dialog → click image → inserts at cursor *(automated endpoint verified; owner confirmed manual selection works)*
- [ ] Click image in editor → image selected (blue handles visible)
- [ ] Double-click image → alt text dialog opens → edit alt → save → alt persists
- [ ] Double-click image → "Hapus Gambar" button → image removed from content, media file preserved
- [ ] Click alignment buttons (left/center/right) with image selected → alignment changes visually in editor *(owner confirmed working)*
- [ ] Click resize buttons (25%/50%/75%/100%) with image selected → width changes visually in editor *(owner confirmed working)*
- [ ] Click "Potong" with image selected → crop overlay appears → drag selection → "Terapkan" → image src updated to cropped version *(automated browser test passed: crop → save → reopen → public rendering all verified; owner confirmed manual crop works)*
- [ ] Click "Potong" → "Batal" → image unchanged *(Cancel button closes dialog without making a request; verified by code inspection)*
- [ ] Click "YouTube" → enter URL → "Sematkan" → responsive video player appears in editor *(owner confirmed working)*
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
- **Fixed article save error `Undefined array key 3` in `SanitizesHtml.php`** — Root cause: the regex alternation `(?:"([^"]*)"|'([^']*)'|(\S+))` returns only the matched capture group plus empty strings for the others in `PREG_SET_ORDER`; for double-quoted empty values (`alt=""`) groups 3 and 4 are omitted entirely. The old code `$attr[2] !== '' ? $attr[2] : ($attr[3] !== '' ? $attr[3] : ($attr[4] ?? ''))` accessed `$attr[3]` unconditionally when group 2 was empty, throwing `Undefined array key 3`. Fix: determine the active capture group by inspecting the first character after `=` in the full match (`"`, `'`, or unquoted), then read the corresponding group with `?? ''`. Empty quoted values are now preserved. Added database transaction around `Articles::save()` so any future failure rolls back article + tag changes without data corruption.
- **Fixed article editor crop failure ("Gagal memotong gambar.")** — Root cause: CI4 CSRF cookie has `HttpOnly=true`, so after any AJAX POST regenerates the CSRF token, JavaScript cannot read the new token from `document.cookie`. Every second POST (e.g., upload then crop) used a stale token and received HTTP 403 `SecurityException`. Additionally, `node.setAttribute('src', ...)` bypassed TinyMCE's internal `data-mce-src` tracking, so cropped image URLs were lost on `triggerSave()`.
  - Fix (server): added `withCsrf()` helper to `Articles` controller that includes `csrf_test_name` in every JSON response body; applied to all 4 AJAX endpoints (upload, crop, media-library, YouTube embed) and all error paths.
  - Fix (client): `refreshCsrf()` now reads the token from the response body instead of the cookie; also updates hidden `input[name="csrf_test_name"]` form fields so the main article save works after AJAX POSTs. Added `.catch()` on `r.json()` for all fetch calls to handle non-JSON 403 responses with Indonesian message "Sesi keamanan telah berubah."
  - Fix (TinyMCE): changed `node.setAttribute('src', ...)` to `editor.dom.setAttrib(node, 'src', ...)` so both `src` and `data-mce-src` are updated, ensuring `triggerSave()` serializes the cropped URL.
  - Media library endpoint response changed from flat array to `{items: [...], csrf_test_name: "..."}` — frontend reads `data.items || data` for backward compatibility.
  - Verified via curl: two consecutive POSTs (YouTube → crop) both succeed with chained CSRF tokens.
  - Verified via browser: crop dialog opens → selection made → Apply → HTTP 200 → dialog closes → image src updated → success notification → save → redirect to articles list → reopen → cropped image persists → public page renders cropped image with alignment/width preserved.
  - Cropped image dimensions verified: 200×150 crop from 400×300 source produces exactly 200×150 output.
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

## Handoff (updated 2026-11-06)

**Committed milestone (on `main`, pushed):** Article editor image management + YouTube embedding fixes are owner-accepted — CSRF token chain fix for sequential AJAX operations, TinyMCE crop src persistence fix, sanitizer `Undefined array key 3` fix, and transaction-wrapped article saves.

**New milestone implemented & verified, NOT yet committed (2026-11-05/06):** YouTube embed selection/deletion (`Hapus Video`, Delete/Backspace, Undo/Redo) + fullscreen (`Layar Penuh`/`Keluar Layar Penuh`) + vertical resize on both article and page editors, plus crop-dialog viewport fix. See the 2026-11-05 section at the top for implementation details, real browser verification results, and the outstanding owner checks (drag-resize gesture, crop drag on a real pointer, playback, visual comfort). Changes: `PROGRESS.md`, `app/Views/admin/article_form.php`, `app/Views/admin/page_form.php`, and new untracked `public/assets/admin/js/editor-controls.js`. Commit/push await owner instruction.

**Remaining requirements before the next feature:**
- Owner manual verification of the new editor features (see "Requires owner manual verification" above).
- Older open items remain listed under **Requires owner manual testing** and **Unverified** (upload/network-error path, animated GIF crop rejection, mobile viewport, prefers-reduced-motion). No new code is needed unless the owner reports issues.
- Data intact (3 pages, 7 menu items, 5 articles, 1 category, 3 tags). Fixtures created during the editor verification (test article id 13, crop media id 47) were removed; all other articles/media — including the owner's own draft test article id 12 — were left untouched.

**Next pending task (do not start until asked):** Implement the standalone media library management screen at `/admin/media` — list uploaded media, allow delete (with guard against use as article cover or inline image), preserve any media referenced by existing articles/pages/portfolio/testimonials. This is the first item under **Next Milestone (Suggested)**.

## 2026-11-07 — Editor preview 403 fix + preset resize sync (uncommitted)

**Issue 1 — CSRF 403 on `/admin/articles/preview-unsaved`:** the Pratinjau button posted the
hidden `csrf_field()` token, which the 2s `fetchFreshCsrf` timer only refreshed AFTER a preview
POST rotated it. A second preview inside that window sent a stale token → SecurityException 403
stack-trace page (dev throws because `Config/Security.php::$redirect` is production-only).
Fixes: article_form Pratinjau handler now `await fetchFreshCsrf()` before submit; new clean
`error_403.php` view ("Artikel tidak tersedia", no trace); new `App\Libraries\SecurityExceptionHandler`
routed from `Config/Exceptions::handler()` so invalid-token POSTs return clean 403 HTML or
`{"error": "Artikel tidak tersedia"}` JSON without leaking exception details; `Blog::show()` throws
404 with the same message (drafts invisible publicly, verified 404 + zero draft content via curl).
Verified: Pratinjau #1 + immediate #2 → both 200; Pratinjau → immediate Save Draft → success flash.

**Issue 2 — preset resize lost the latest width:** `setImgResize()` set the live `style` but not
TinyMCE's `data-mce-style` snapshot, which the serializer prefers on output. Drag→35% then preset
50% reproduced it: editor showed 50%, submitted HTML + preview stayed 35%. Fix: mirror live style
into `data-mce-style` before `nodeChanged()` in `article_form.php`. Measured (editor body 778px,
unsaved preview column 696px): drag 25→50% → editor 389/778 (50.0%), HTML `width: 50%`, preview
348/696 (50.0%); drag 50→35% → 272/778, HTML 35%, preview 244/696 (35.1%); preset 50% after drag →
389/778, HTML 50%, preview 348/696 — all match. Saved chain on temp article: DB `width: 50%`,
editor reopen 50.0% (aspect 0.751, align center kept), eye-button preview 348/696 (50.0%).

**Cleanup:** temp article id 5 (`temp-uji-resize-csrf`) deleted; articles 1–4 intact; article 4
draft + published_at NULL. Note: article 4's body was last modified 2026-11-06 23:56 by the
previous session's own save test (blank-line work) — today's session never wrote to it.

**Notes for the next session:**
- Uncommitted work now spans TWO milestones: the 2026-11-05 editor usability milestone (YouTube deletion + fullscreen/resize) and the 2026-11-06 unsaved-preview milestone. Commit only after owner acceptance of both, and never force-push.

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
