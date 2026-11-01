# Portal — Product Requirements Document

Version: 1.0 | Date: 2026-11-01 | Status: Ready for repository inspection

## 1. Purpose

Build a personal publishing website with an authenticated CMS. The owner must manage articles, categories, tags, images, videos, navigation, and header images without editing source code. Use the supplied template as the visual foundation. Preserve recoverable copies of code, database content, and uploaded files.

This document is the implementation source of truth for OpenCode. Requirements below are agreed; actual files, installed versions, and template compatibility still require inspection. Do not claim verification without evidence.

## 2. Technology and Existing Files

| Item | Required value |
|---|---|
| Project | Portal |
| Local project directory | `/Volumes/Data/Project/portal` |
| Framework | CodeIgniter 4.7.5; use supplied files |
| PHP | 8.5.12 |
| Web server | Apache 2.4.69 |
| Database server | MariaDB 11.4.13 |
| Database name | `portal_db` |
| Bootstrap source | Existing `bootstrap-5` folder; inspect exact version |
| Design reference | Existing `page-template` folder |
| AI coding tool | OpenCode |
| Version control | Git with a remote repository |

Configure Apache DocumentRoot to the project's `public` directory. Keep application code, configuration, logs, and private files outside public access. Store secrets in an ignored `.env`; provide `.env.example` with placeholders only. Local database username is `root`; obtain its supplied password through local configuration, never this PRD, logs, or Git. Production must use a dedicated database account with only required privileges.

Before implementation, inspect framework files, Composer requirements, available PHP extensions, Bootstrap assets, template dependencies, database connectivity, and Git status. Preserve existing work. Report version mismatches and select a compatible approach before replacing dependencies or altering the supplied framework.

## 3. Users and Scope

- Visitor: browse and search published content, filter by category/tag, view media, and share article links.
- Administrator: log in and manage all CMS content and website settings.

Initial release has one administrator role and no public registration. Exclude comments, memberships, payments, AI content generation, direct social publishing, and advanced editorial workflows. Public UI and admin UI default to Indonesian; code identifiers and technical documentation use English. UI language is an initial implementation default, not a multilingual requirement.

## 4. Functional Requirements

### FR-01 — Public Website and Template

- Adapt supplied layouts into reusable CodeIgniter views: header, navigation, footer, article cards, and page content.
- Provide home, paginated article listing, article detail, category listing, tag listing, search, and a styled 404 page.
- Search published article titles and body text; preserve search/filter state during pagination.
- Show article title, cover when available, excerpt, publication date, category, tags, formatted body, and share controls.
- Preserve template character, spacing, typography, and image placement while making layouts responsive.
- Inspect Bootstrap compatibility before integration. Avoid loading conflicting Bootstrap versions on the same page; adapt template markup as needed.
- Keep original reference folders intact; serve runtime assets from organized directories under `public`.

### FR-02 — Administrator Authentication

- Provide login, logout, password change, and a protected dashboard with content counts and recent articles.
- Protect every admin route and mutation endpoint with server-side authentication.
- Hash passwords using supported PHP password hashing APIs. Regenerate the session identifier after login, invalidate it on logout, and throttle failed login attempts.
- Use CSRF protection for state-changing requests. Deletion and logout must not occur through GET requests.
- Create the initial administrator through a documented setup command with secure password input. Do not commit default credentials or reuse the database password.
- Match login/admin colors, typography, and branding to the supplied template; use an appropriate CMS layout when no admin template exists.

### FR-03 — Categories and Tags

- List, create, edit, and delete categories and tags with validation and unique slugs within each entity type.
- Initial model: one category and zero or more tags per article.
- Prevent deletion of a category used by articles until those articles are reassigned.
- Deleting a tag removes its article associations without deleting articles. Require confirmation for deletion.

### FR-04 — Articles and Publishing

- List articles with pagination and search; filter by status and category.
- Create, edit, and delete articles. Fields: title, unique slug, optional excerpt, category, tags, body, optional cover image, status, and publication date.
- Support `draft` and `published`. New articles default to draft. Publishing requires title, category, slug, and nonempty body.
- Assign publication time on first publication; preserve it during later edits unless explicitly changed. Scheduled publication is out of scope.
- Provide Save Draft, Preview, Publish, and Unpublish actions. Do not discard unsaved editor content during preview.
- Preview the current editor content in the public article layout through an authenticated flow. Preview responses must not be publicly accessible, indexed, or cached as public content.
- Public routes, searches, listings, and metadata must never expose drafts or preview content.
- Warn before leaving an article form with unsaved changes. Show clear validation, success, and failure feedback.
- Confirm article deletion. Delete associations without deleting shared media files.

### FR-05 — Rich Text Editor

- Include headings, paragraphs, bold, italic, underline, strikethrough, ordered/unordered lists, alignment, blockquotes, links, undo/redo, tables, images, and YouTube/TikTok embeds.
- Insert images through upload or media selection; allow alt text, captions, and responsive sizing. Add/remove/replace covers separately from inline images.
- Select a maintained editor and server-side HTML sanitizer compatible with the project. Document licenses, feature availability, and dependencies. Required features must not silently depend on a paid plan or an owner's cloud subscription.
- Sanitize saved HTML on the server using an explicit allowlist. Remove scripts, event handlers, unsafe URLs, and unsupported markup. Escape ordinary text fields at output.
- Preserve supported formatting and embeds after saving and reopening the editor.

### FR-06 — Media and Video

- Provide searchable, paginated media management for images and uploaded videos, including title, alt text for images, caption, and file details.
- Validate server-side MIME/content, extension, and configurable size limits. Generate safe unique filenames; reject executable files and active formats such as SVG for the initial release.
- Initial upload defaults: JPEG/PNG/WebP images up to 10 MiB; MP4 videos up to 100 MiB. Align PHP/web-server limits and display configured limits in forms.
- Use responsive images and suitable thumbnails; preserve original uploads. Uploaded videos use HTML5 playback without automatic transcoding.
- Support managed YouTube/TikTok video entries using provider URLs; allow insertion into articles.
- Parse and validate provider URLs server-side. Generate embeds through controlled templates; do not accept arbitrary iframe HTML or arbitrary remote fetch URLs.
- Embedded players may require external internet access. Provide a source link when the provider cannot display the player; do not promise offline playback.
- Track media references in article bodies, covers, video entries, and header settings. Block deletion while referenced and identify affected content.
- Failed database/file operations must not leave broken references. Clean up temporary uploads safely.

### FR-07 — Sharing

- Provide dedicated WhatsApp, Facebook, X, and Copy Link controls on published articles.
- Share the canonical public article URL with correctly encoded values. No social account credentials or automatic posting are required.
- Include article-specific page title, description, canonical URL, and Open Graph metadata with a cover fallback for social previews. Platform preview behavior is external to the application.

### FR-08 — Header and Site Settings

- Configure site name, short description, and the header image areas actually present in the supplied template.
- Select/upload, replace, or remove header images, with a default fallback. Display recommended dimensions based on the inspected template.
- Do not invent sliders or additional header regions unless the supplied template requires them.

### FR-09 — Navigation

- Create, edit, delete, reorder, enable, and disable menu entries.
- Support internal article/category links, custom URLs, and at most one submenu level.
- Validate URLs, reject unsafe schemes, prevent parent/child cycles, and preserve a working mobile menu.
- Exclude draft article targets from public navigation. Apply safe attributes when opening external links in a new tab.

## 5. Data and Architecture

Use CodeIgniter controllers, models, services, views, validation, filters, migrations, and seeders. Keep business rules out of views. Use parameterized queries or the framework query builder.

Minimum conceptual entities:

| Entity | Purpose / relationships |
|---|---|
| Admin users | Login identity and password hash |
| Categories | Unique name/slug; referenced by articles |
| Tags | Unique name/slug |
| Articles | Author, category, content, status, cover, publication timestamp |
| Article tags | Unique article/tag associations |
| Media | Upload metadata, storage path, type, alt text, caption |
| Videos | Uploaded media reference or validated provider URL |
| Content media references | Track shared files used by articles and settings |
| Menu items | Position, optional parent, link type/target, enabled state |
| Site settings | Site identity and template header configuration |

Finalize physical tables during inspection/design. Use foreign keys, appropriate indexes, timestamps, transactions, `utf8mb4`, and uniqueness constraints. Store timestamps consistently and display dates in `Asia/Jakarta`. Slug changes must not create duplicate routes; provide redirects from previous published article slugs.

## 6. Quality Requirements

- Responsive public/admin layouts on mobile, tablet, and desktop; no unintended horizontal scrolling.
- Label forms, support keyboard navigation, show visible focus states, and provide image alt text fields.
- Paginate content/media lists and avoid unbounded queries. Lazy-load suitable images and video embeds.
- Serve Bootstrap and ordinary template assets locally; document any remaining external dependencies and licenses.
- Validate all input server-side. Protect uploads from execution and unauthorized writes; apply safe session cookies, including Secure under HTTPS.
- Production must disable debug output and hide stack traces/secrets from visitors. Log useful failures without credentials or private article bodies.
- Keep changes scoped to this project. Never modify unrelated services or delete existing databases/files to resolve setup errors.

## 7. Recovery and Git

- Initialize or reuse Git after checking existing status. Commit migrations, dependency lockfiles, application code, and required static assets.
- Ignore `.env`, credentials, logs, caches, database dumps, private backups, runtime uploads, and regenerable dependencies. Retain required writable-directory placeholders.
- Use small milestone commits with clear messages. Configure and push to the owner's chosen remote; never invent a remote, overwrite remote history, or force-push.
- Provide a documented backup command for a consistent MariaDB dump plus uploaded files and a manifest identifying backup time, application commit, and included paths.
- Initial retention target: seven daily and four weekly recovery points, in a destination outside the project directory with a second independent copy. Treat this as a proposed operating policy; document the selected destination and scheduler before calling backups automated.
- Keep secret configuration in a separate protected recovery location; do not put credentials in ordinary backup manifests or Git.
- Document clean installation, backup, restore, and administrator recovery. Demonstrate database/media restoration into an isolated test target without overwriting the working project.
- Local commits alone are not a remote backup; a remote code push does not back up database content or uploaded media.

## 8. Implementation Milestones

| Phase | Deliverables | Completion gate |
|---|---|---|
| 0. Inspect | File/version report, template mapping, dependency/license review, compatibility decisions | Verified findings distinguished from assumptions; no destructive changes |
| 1. Foundation | Environment example, database migrations, admin setup/login, protected dashboard, Git ignore rules | App boots; DB connects; login/logout and denied anonymous access verified |
| 2. Content | Category/tag CRUD, media library, article editor, covers, draft/preview/publish | Full content workflow works; drafts stay private; unsafe input/upload checks pass |
| 3. Public site | Template integration, listings/detail/search, media playback, share metadata | Published content renders correctly on mobile/desktop with working filters and links |
| 4. Settings | Menus, header images, site identity | Changes persist and appear publicly; referenced-media deletion blocked |
| 5. Recovery/release | Backup/restore instructions, isolated restore test, milestone commits, remote push setup | Acceptance checks pass; code push and data/media recovery evidence recorded separately |

Implement one milestone at a time. At each milestone, report files changed, commands/checks run, results, remaining blockers, and the next action. Maintain `PROGRESS.md` with completed work, pending work, and known issues so another model can resume without repeating completed tasks. Do not mark a phase complete based only on code generation.

## 9. Release Acceptance

1. An anonymous visitor cannot access any admin mutation or private preview.
2. Admin can create/edit/delete unused categories and tags; referenced-category rules work.
3. Admin creates a draft with formatted text, table, inline image, cover, YouTube, and TikTok; reopening preserves supported content.
4. Preview reflects unsaved editor content and remains private. Draft URLs return no article content publicly.
5. Publishing makes the article visible in listings, search, category/tag results, and its detail page. Unpublishing removes public access.
6. Cover replacement/removal and media reference protection work without breaking other content.
7. Uploaded video playback and provider fallback links work; unsupported or oversized uploads fail clearly.
8. Menu ordering, visibility, submenu behavior, and header image changes persist across restart.
9. Share controls use the correct public URL; page metadata reflects the published article.
10. CSRF, stored HTML sanitization, unsafe embed URLs, login throttling, and upload validation have meaningful checks.
11. Public/admin pages pass manual mobile and desktop review with no material layout errors.
12. A fresh installation can apply migrations and create an administrator using documentation.
13. Restoring a backup to an isolated target recovers articles, relationships, settings, and uploaded media.
14. Git contains no secrets or runtime uploads; the remote receives intended commits when configured.

## 10. Items to Resolve from Actual Files

- Exact Bootstrap version and template dependencies/license.
- Available template pages and header image regions.
- Compatible rich text editor/sanitizer and embed approach.
- Local/public base URLs, Apache configuration, and effective PHP upload limits.
- Remote repository/provider, backup destination, and automation method.

Resolve routine implementation choices from repository evidence and document them. Ask the owner only when an unresolved choice changes scope, cost, access, or intended behavior. Do not replace unavailable evidence with assumptions presented as facts.
