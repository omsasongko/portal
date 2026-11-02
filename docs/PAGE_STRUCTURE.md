# Portal — Public Homepage Structure & CMS Mapping

Version: 1.0 | Date: 2026-11-01 | Status: Documentation / planning only

## 1. Scope & Sources

This document maps the current public homepage into reusable CMS-managed components. It is based on inspection of:

- `app/Views/portal/home.php` (runtime view)
- `page-template/index.html` (design reference)
- `PROGRESS.md` and `PRD_Portal_v1.0.md`

**Design constraints:**
- Preserve Bootstrap 5 integration and responsive behavior.
- Reuse planned modules: Articles, Media, Menus, and Settings.
- Avoid duplicate data sources (e.g. blog cards must come from the Articles module).
- Content is managed through structured settings and repeatable items, not arbitrary HTML/CSS or a drag-and-drop builder.

## 2. Global Layout Components

These components appear on the homepage and will also be used on other public pages.

### 2.1 HTML Head
| Item | Detail |
|---|---|
| ID / selector | `<head>` |
| View location | `app/Views/portal/home.php` lines 3–36 |
| Purpose | Meta tags, page title, favicon, asset loading. |
| Editable fields | Site title, meta keywords, meta description, meta author, favicon, apple-touch-icon. |
| Field types | text, textarea, image upload (media reference). |
| Defaults | Hard-coded template values today. |
| Validation | Title ≤ 70 chars; description ≤ 160 chars; images must be ICO/PNG. |
| Repeatable items | — |
| Media references | `assets/portal/images/favicon.ico`, `assets/portal/images/apple-touch-icon.png` |
| Visibility / ordering | Always visible; loaded per page. |
| Proposed admin screen | **Settings → Site Identity / SEO** |
| Proposed storage | `site_settings` table (key/value or JSON). |

### 2.2 Page Loader
| Item | Detail |
|---|---|
| ID / selector | `#preloader` |
| View location | `home.php` lines 39–45 |
| Purpose | Visual loader shown while page assets load. |
| Editable fields | Enable/disable loader. |
| Field type | boolean toggle. |
| Default | Enabled. |
| Validation | — |
| Media references | Uses CSS background/animation; no CMS media. |
| Proposed admin screen | **Settings → Global Appearance** |
| Proposed storage | `site_settings` table. |

### 2.3 Main Navigation
| Item | Detail |
|---|---|
| ID / selector | `#mainNav` |
| View location | `home.php` lines 47–83 |
| Purpose | Fixed-top responsive navbar with brand logo and scroll anchors. |
| Editable fields | Brand logo (media reference). Menu items are managed by the Menu module. |
| Field types | image upload; menu selection. |
| Defaults | Logo = `assets/portal/images/logo.png`; menu items = Home, About, Services, Portfolio, Testimonials, Blog, Contact. |
| Validation | Logo: JPG/PNG/WebP ≤ 2 MiB. Menu anchors must match existing section IDs (e.g. `#about`, `#services`). |
| Repeatable items | Menu items (reuse `menu_items` table). |
| Media references | `assets/portal/images/logo.png` |
| Visibility / ordering | Always visible on public pages; menu order comes from `menu_items.position`. |
| Proposed admin screen | **Settings → Site Identity** (logo); **Menus** (navigation items). |
| Proposed storage | `site_settings` (logo); `menu_items` table (navigation). |

### 2.4 Footer / Copyrights
| Item | Detail |
|---|---|
| ID / selector | `.copyrights` |
| View location | `home.php` lines 501–518 |
| Purpose | Footer links and copyright line. |
| Editable fields | Footer link menu, site name, copyright year, additional credit line. |
| Field types | menu selection, text, year number. |
| Defaults | Hard-coded links and text. |
| Validation | URLs validated; year numeric. |
| Repeatable items | Footer links (reuse `menu_items` table with a different menu group). |
| Visibility / ordering | Always visible; links ordered by menu position. |
| Proposed admin screen | **Menus → Footer Menu**; **Settings → Site Identity**. |
| Proposed storage | `menu_items` table (group = `footer`); `site_settings` table. |

### 2.5 Scroll-to-Top Button
| Item | Detail |
|---|---|
| ID / selector | `#scroll-to-top` |
| View location | `home.php` line 520 |
| Purpose | Global back-to-top button. |
| Editable fields | Show/hide toggle. |
| Field type | boolean. |
| Default | Enabled. |
| Proposed admin screen | **Settings → Global Appearance** |
| Proposed storage | `site_settings` table. |

## 3. Homepage Sections

### 3.1 Hero Section
| Item | Detail |
|---|---|
| HTML ID | `#home` |
| View location | `home.php` lines 85–124 |
| Purpose | Full-height banner with background image, headline, subheadline, and rotating animated words. |
| Editable fields | Background image, heading text, subheading text, use rotating words (boolean), rotating words list, hero layout preset. |
| Field types | media reference, text (heading, subheading), boolean, repeatable text list, select (layout preset). |
| Defaults | Background = `uploads/banner-01.jpg`; heading = "hello i'm Dominic"; subheading = template lorem text; rotating words = Web Developer, Web Design, Creative Design, Graphic Design. |
| Validation | Heading ≤ 120 chars; background image required (JPG/PNG/WebP ≤ 10 MiB); at least one rotating word if enabled. |
| Repeatable items | Rotating words. |
| Media references | `assets/portal/uploads/banner-01.jpg` |
| Visibility / ordering | First section on homepage; can be hidden via section toggle. |
| Layout options | `hero_rotating` (current) or `hero_static` (no rotating words). |
| Proposed admin screen | **Homepage → Hero** |
| Proposed storage | `homepage_sections` table (row `section = 'hero'`) with JSON `content`; media_id references `media` table. |

**Notes:** The cloud SVG divider (`#clouds`) is decorative and should stay with the hero layout. It is not a separately managed element.

### 3.2 About Section
| Item | Detail |
|---|---|
| HTML ID | `#about` |
| View location | `home.php` lines 126–146 |
| Purpose | Two-column about block: text + image. |
| Editable fields | Heading, body (rich text), image, button text, button link, image position/alignment. |
| Field types | text, rich text (sanitized HTML), media reference, text, url, select. |
| Defaults | Heading = "About Dominic."; image = `uploads/about_04.jpg`; button = "Download CV" (link `#`). |
| Validation | Body required; image required; URL safe. |
| Repeatable items | — |
| Media references | `assets/portal/uploads/about_04.jpg` |
| Visibility / ordering | Section can be enabled/disabled and ordered among homepage sections. |
| Layout options | `image_right` (current), `image_left`, `text_only`. |
| Proposed admin screen | **Homepage → About** |
| Proposed storage | `homepage_sections` table (row `section = 'about'`) with JSON `content` and media_id. |

### 3.3 Services Section
| Item | Detail |
|---|---|
| HTML ID | `#services` |
| View location | `home.php` lines 148–212 |
| Purpose | Grid of service/feature cards with icon, title, and description. |
| Editable fields | Section title, section subtitle, service cards (icon CSS class, title, description). |
| Field types | text, textarea, repeatable group (icon class, title, description). |
| Defaults | Title = "Services"; 6 cards with Flaticon icons. |
| Validation | Each card title required, description required; max 6 cards recommended for 3-column layout. |
| Repeatable items | Service cards. |
| Media references | Icons are CSS font icons (Flaticon), not uploaded media. |
| Visibility / ordering | Section toggle; order among homepage sections. |
| Layout options | `grid_3_columns` (current), `grid_2_columns`. |
| Proposed admin screen | **Homepage → Services** with inline list of cards. |
| Proposed storage | `homepage_sections` table (row `section = 'services'`); `homepage_section_items` table for cards (parent_id, position, JSON content). |

### 3.4 Portfolio / Gallery Section
| Item | Detail |
|---|---|
| HTML ID | `#portfolio` |
| View location | `home.php` lines 214–289 |
| Purpose | Filterable image gallery using Isotope + prettyPhoto. |
| Editable fields | Section title, subtitle, filter definitions (label + CSS class), gallery items (image, title/alt, filter classes). |
| Field types | text, textarea, repeatable groups. |
| Defaults | Filters: All, Web Development, Creative Design, Graphic Design; 6 sample images. |
| Validation | Each item must map to at least one existing filter class; image required. |
| Repeatable items | Filter definitions; gallery items. |
| Media references | `assets/portal/uploads/gallery_img-01.jpg` … `gallery_img-06.jpg` |
| Visibility / ordering | Section toggle; order among homepage sections. |
| Layout options | `masonry_3_columns` (current), `grid_2_columns`. |
| Proposed admin screen | **Homepage → Portfolio** with filter manager and item list. |
| Proposed storage | `homepage_sections` row (`section = 'portfolio'`); dedicated `portfolio_categories`, `portfolio_items`, and `portfolio_item_categories` (pivot) tables. `filter_class` is a validated unique CSS class (`^[a-zA-Z][a-zA-Z0-9_-]*$`). |

### 3.5 Testimonials Section
| Item | Detail |
|---|---|
| HTML ID | `#testimonials` |
| View location | `home.php` lines 291–380 |
| Purpose | Carousel of testimonial cards (quote heading, quote body, author avatar, author name, role). |
| Editable fields | Section title, subtitle, testimonial cards (quote heading, quote body, author name, author role, avatar). |
| Field types | text, textarea, repeatable group, media reference. |
| Defaults | Title = "Testimonials"; 6 testimonial cards using 3 avatars. |
| Validation | Quote body and author name required; avatar optional. |
| Repeatable items | Testimonial cards. |
| Media references | `assets/portal/uploads/testi_01.png`, `testi_02.png`, `testi_03.png` |
| Visibility / ordering | Section toggle; order among homepage sections. |
| Layout options | `carousel_2_per_slide` (current), `carousel_1_per_slide`. |
| Proposed admin screen | **Homepage → Testimonials** with inline cards. |
| Proposed storage | `homepage_sections` row (`section = 'testimonials'`); `homepage_section_items` for cards. |

### 3.6 Blog / Latest Articles Section
| Item | Detail |
|---|---|
| HTML ID | `#blog` |
| View location | `home.php` lines 382–453 |
| Purpose | Show a preview of the latest published articles. |
| Editable fields | Section title, subtitle, source (latest articles / category / tag), number of posts to show, show date, show author, show excerpt. |
| Field types | text, select, integer, booleans. |
| Defaults | Title = "Blog"; 3 cards from latest articles. |
| Validation | Limit between 1 and 12. |
| Repeatable items | Articles are pulled automatically from the Articles module. |
| Media references | Article cover images from `media` table. |
| Visibility / ordering | Section toggle; order among homepage sections. |
| Layout options | `grid_3_columns` (current), `list_2_columns`. |
| Proposed admin screen | **Homepage → Blog Preview** (select source and display count). |
| Proposed storage | `homepage_sections` row (`section = 'blog'`); data read from `articles`, `categories`, `tags`, and `media` tables. |

**Article sidebar proposal (optional, future):** Add a sidebar variant `blog_with_sidebar` that can display a search box, category list, and tag cloud alongside the article grid. This would reuse the same article/listing modules and would be enabled through the layout option once article detail pages exist.

### 3.7 Contact Section
| Item | Detail |
|---|---|
| HTML ID | `#contact` |
| View location | `home.php` lines 455–499 |
| Purpose | Contact form (name, email, phone, message). |
| Editable fields | Section title, subtitle, recipient email, success message, form labels/button text. |
| Field types | text, email, textarea, booleans. |
| Defaults | Hard-coded labels and "Send Message" button. |
| Validation | Recipient email required and valid. Form fields are validated client-side by `jqBootstrapValidation` and server-side by the contact endpoint. |
| Repeatable items | — |
| Media references | — |
| Visibility / ordering | Section toggle; order among homepage sections. |
| Proposed admin screen | **Homepage → Contact** |
| Proposed storage | `homepage_sections` row (`section = 'contact'`); recipient stored in `site_settings`. |

## 4. Proposed Database Model

### 4.1 Core Settings
- `site_settings`
  - `id` PK
  - `group` (e.g. `site_identity`, `global_appearance`, `contact`)
  - `key`
  - `value` (text/JSON)
  - timestamps

### 4.2 Menu Module (reused)
- `menu_items`
  - `id` PK
  - `group` (`main`, `footer`)
  - `parent_id` (nullable, for one submenu level)
  - `label`
  - `url`
  - `target` (`_self`, `_blank`)
  - `position`
  - `is_enabled`
  - timestamps

### 4.3 Media Module (reused)
- `media`
  - `id` PK
  - `title`
  - `filename`
  - `mime_type`
  - `alt_text`
  - `caption`
  - `is_referenced` (or tracked via `content_media_refs`)
  - timestamps

### 4.4 Homepage Section Storage
- `homepage_sections`
  - `id` PK
  - `section` (unique: `hero`, `about`, `services`, `portfolio`, `testimonials`, `blog`, `contact`, …)
  - `title` (admin label)
  - `layout` (preset key)
  - `is_enabled`
  - `sort_order`
  - `content` (JSON with section-specific fields)
  - `media_id` (nullable, FK to `media` for single-image sections)
  - timestamps

- `homepage_section_items`
  - `id` PK
  - `homepage_section_id` FK
  - `sort_order`
  - `item_type` (e.g. `card`, `filter`, `gallery_item`, `testimonial`)
  - `content` (JSON)
  - `media_id` (nullable, FK to `media`)
  - timestamps

### 4.5 Articles Module (reused for Blog section)
- `articles`, `categories`, `tags`, `article_tags` (as defined in PRD).
- Blog section queries published articles only, respecting status and publication date.

## 5. Data Relationships

```
site_settings
 ├── site title, meta, favicon, contact email, loader toggle, scroll-to-top toggle
 ├── navigation logo
 └── footer copyright text

menu_items (group = main | footer)
 ├── mainNav links (Home, About, …)
 └── footer links

media
 ├── hero background
 ├── about image
 ├── portfolio gallery images
 ├── testimonial avatars
 ├── article covers
 └── site favicon / apple-touch-icon

homepage_sections
 ├── hero
 ├── about
 ├── services
 ├── portfolio
 ├── testimonials
 ├── blog  → reads from articles module
 └── contact  → uses contact email from site_settings

homepage_section_items
 ├── service cards (services)
 ├── filter definitions (portfolio)
 ├── gallery items (portfolio)
 └── testimonial cards (testimonials)

articles / categories / tags
 └── blog section source
```

## 6. Proposed Implementation Order

1. **Foundation:** Create `docs/PAGE_STRUCTURE.md` (this document) and update `PROGRESS.md`.
2. **Database:** Implement `site_settings`, `menu_items`, `media`, and `homepage_sections` + `homepage_section_items` migrations.
3. **Admin UI:**
   - Site Identity / Global Appearance settings.
   - Menu manager (main + footer).
   - Media library.
4. **Homepage Builder:**
   - Hero, About, Services, Portfolio, Testimonials, Contact admin forms.
   - Section enable/sort interface.
5. **Public View Refactor:**
   - Split `home.php` into reusable partials (`_header.php`, `_nav.php`, `_hero.php`, `_about.php`, …, `_footer.php`).
   - Replace hard-coded content with data from new tables.
6. **Article Integration:** Build Articles module, then wire Blog section to pull published articles.
7. **Validation & Recovery:** Add server-side validation, media reference protection, and backup documentation.

## 7. Unresolved Decisions

| # | Decision | Options | Recommendation |
|---|---|---|---|
| 1 | Rich-text body storage for About/Service/Portfolio/Testimonial | Plain text only vs. sanitized HTML | Use sanitized HTML with an allowlist; align with PRD FR-05. |
| 2 | Homepage section content format | Strict table columns vs. JSON `content` column | Use JSON `content` for flexibility, with admin forms enforcing per-section schema. |
| 3 | Portfolio filters | Hard-coded classes in item form vs. managed filter definitions | Manage filter definitions in `homepage_section_items` and validate item mappings. |
| 4 | Blog section source | Latest articles only vs. category/tag filter | Start with latest articles; add category/tag filter after Articles module is complete. |
| 5 | Article sidebar | Include now vs. defer | Defer until article detail/listing pages exist; note as proposal. |
| 6 | Contact form backend | Store in database vs. send email vs. both | Store submissions in `contact_messages` table and optionally send email. |

## 8. Verification vs. Proposals

- **Verified findings:**
  - Homepage IDs: `#home`, `#about`, `#services`, `#portfolio`, `#testimonials`, `#blog`, `#contact`.
  - Global components: `#preloader`, `#mainNav`, `.copyrights`, `#scroll-to-top`.
  - Blog cards currently use static placeholders; they must come from the Articles module.
  - Portfolio uses Isotope with filter classes `.gal_a`, `.gal_b`, `.gal_c`.
  - Testimonials use Owl Carousel.

- **Proposals:**
  - `homepage_sections` / `homepage_section_items` tables.
  - Reuse of `menu_items`, `media`, and `articles` modules.
  - Layout presets and section ordering UI.
  - Optional article sidebar.
