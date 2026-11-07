<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\HomepageSectionModel;
use App\Models\TagModel;
use App\Models\SettingModel;

/**
 * Public blog listing.
 *
 * Filter URLs are canonical and readable, built ONLY by blogUrl():
 *   /blog?categories=kesehatan,kebugaran&tags=kardio&q=...&page=2
 * - slugs are deduplicated and sorted consistently
 * - q only when non-empty, page only when > 1
 * - legacy numeric/array URLs (category=3, category[]=3, tag[]=4,
 *   categories[]=a&categories[]=b) are accepted and 302-redirected to the
 *   canonical form — the redirect IS the normalization.
 *
 * Async updates fetch GET blog/results (X-Requested-With header), which
 * returns JSON {html, canonicalUrl, total, page, totalPages} — only the
 * results column HTML travels, not the whole document.
 */
class Blog extends BaseController
{
    protected ArticleModel $articleModel;

    private const PER_PAGE = 10;
    private const MAX_Q_LENGTH = 100;
    private const MAX_TOKEN_LENGTH = 100;
    private const MAX_SELECTED_PER_GROUP = 20;
    private const MAX_PAGE = 10000;

    public function __construct()
    {
        helper('menu');
        $this->articleModel = new ArticleModel();
    }

    /**
     * Full document listing at /blog.
     */
    public function index()
    {
        $parsed = $this->parseFilterRequest();

        // Legacy / array-shaped / non-canonical request → redirect to the
        // canonical slug URL once, so every shared link converges on the
        // same readable form. The parsed (validated) values are used, so
        // unknown slugs simply vanish and no redirect loop is possible.
        if ($parsed['needsNormalize']) {
            return redirect()->to($this->blogUrl(
                $parsed['categorySlugs'],
                $parsed['tagSlugs'],
                $parsed['q'],
                $parsed['page']
            ));
        }

        $data = array_merge($this->listingData($parsed), [
            'resultsUrl' => base_url('blog/results'),
            'identity'   => $this->identity(),
            'menuItems'  => build_public_menu(true),
        ]);

        return view('portal/blog_list', $data);
    }

    /**
     * Partial results endpoint for async filter updates. Returns ONLY the
     * results column (chips + cards/empty + pagination) as HTML inside a
     * small JSON envelope, with public visibility rules applied exactly as
     * on the full page.
     */
    public function results()
    {
        $parsed = $this->parseFilterRequest();

        // Direct document navigation (no XHR) → land on the full page.
        if (! $this->request->isAJAX()) {
            return redirect()->to($this->blogUrl(
                $parsed['categorySlugs'],
                $parsed['tagSlugs'],
                $parsed['q'],
                $parsed['page']
            ));
        }

        $data = $this->listingData($parsed);

        return $this->response
            ->setJSON([
                'html'         => view('portal/blog_results', $data),
                'canonicalUrl' => $this->blogUrl(
                    $parsed['categorySlugs'],
                    $parsed['tagSlugs'],
                    $parsed['q'],
                    $data['page']
                ),
                'total'      => $data['total'],
                'page'       => $data['page'],
                'totalPages' => $data['totalPages'],
            ])
            ->setHeader('Cache-Control', 'no-store');
    }

    /**
     * Public article detail at /blog/{slug}. Drafts, deleted, and missing
     * articles are not publicly accessible (404).
     */
    public function show(string $slug)
    {
        $article = $this->articleModel->findPublishedBySlug($slug);

        if (! $article) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Artikel tidak tersedia');
        }

        $categoryModel = new CategoryModel();
        $tagModel      = new TagModel();

        $categoryIds = $this->articleModel->categoryIdsForArticle((int) $article['id']);
        $categories  = $categoryIds ? $categoryModel->whereIn('id', $categoryIds)->orderBy('name', 'ASC')->findAll() : [];
        $tagIds      = $this->articleModel->tagIdsForArticle((int) $article['id']);
        $tags        = $tagIds ? $tagModel->whereIn('id', $tagIds)->findAll() : [];

        $related = ($article['detail_layout'] === 'sidebar')
            ? $this->articleModel->relatedPublished((int) $article['id'], $categoryIds)
            : [];

        return view('portal/blog_detail', [
            'article'    => $article,
            'categories' => $categories,
            'tags'       => $tags,
            'related'    => $related,
            'identity'   => $this->identity(),
            'menuItems'  => build_public_menu(true),
            'isPreview'  => false,
        ]);
    }

    // ------------------------------------------------------------------
    // Filter parsing + canonical URL building (single source of truth)
    // ------------------------------------------------------------------

    /**
     * Read and validate every filter parameter. Accepts:
     * - categories=kesehatan,kebugaran (canonical, comma separated)
     * - categories[]=kesehatan&categories[]=kebugaran (no-JS form fallback)
     * - category=3 / category[]=3 / tag=4 / tag[]=4 (legacy numeric URLs)
     * Tokens may be existing slugs or numeric ids (mapped to their slug).
     * Types, lengths and counts are capped; unknown values are dropped
     * silently; q is trimmed and capped.
     *
     * @return array{q:string, categorySlugs:string[], tagSlugs:string[], categoryIds:int[], tagIds:int[], page:int, needsNormalize:bool}
     */
    private function parseFilterRequest(): array
    {
        $request = $this->request;

        $q = trim($this->scalarParam($request->getGet('q')));
        if (mb_strlen($q) > self::MAX_Q_LENGTH) {
            $q = mb_substr($q, 0, self::MAX_Q_LENGTH);
        }

        [$catTokens, $catWasArray]     = $this->tokensFromParam($request->getGet('categories'));
        [$tagTokens, $tagWasArray]     = $this->tokensFromParam($request->getGet('tags'));
        $catLegacyRaw = $request->getGet('category');
        $tagLegacyRaw = $request->getGet('tag');
        [$catLegacyTokens] = $this->tokensFromParam($catLegacyRaw);
        [$tagLegacyTokens] = $this->tokensFromParam($tagLegacyRaw);

        $categories = $this->resolveGroup(array_merge($catTokens, $catLegacyTokens), new CategoryModel());
        $tags       = $this->resolveGroup(array_merge($tagTokens, $tagLegacyTokens), new TagModel());

        // Page: a scalar, clamped to a sane upper bound (prevents integer
        // overflow in the LIMIT offset math). Out-of-range values later clamp
        // to the last page after the total is known.
        $pageRaw = $request->getGet('page');
        $pageStr = is_scalar($pageRaw) ? trim((string) $pageRaw) : '';
        $page    = max(1, min((int) $pageStr, self::MAX_PAGE));

        // Normalize (redirect) when the request differs from its canonical
        // shape: legacy params, array params, duplicated slugs, or a page
        // value that is not exactly its clamped integer. A valid canonical-
        // shaped URL in any slug order is accepted as-is (no redirect); the
        // canonical builder still emits one consistent order everywhere.
        $needsNormalize = $catLegacyRaw !== null
            || $tagLegacyRaw !== null
            || $catWasArray
            || $tagWasArray
            || (count($categories['inOrder']) !== count($categories['slugs']))
            || (count($tags['inOrder']) !== count($tags['slugs']))
            || ($pageRaw !== null && (string) $page !== $pageStr);

        return [
            'q'             => $q,
            'categorySlugs' => $categories['slugs'],
            'tagSlugs'      => $tags['slugs'],
            'categoryIds'   => $categories['ids'],
            'tagIds'        => $tags['ids'],
            'page'          => $page,
            'needsNormalize' => $needsNormalize,
        ];
    }

    /**
     * Coerce a raw GET value to a string for scalar parameters. Arrays and
     * null become '' so a malformed array input (e.g. q[]=x) degrades to "no
     * value" instead of triggering a PHP "Array to string conversion" warning.
     */
    private function scalarParam($value): string
    {
        if ($value === null || is_array($value)) {
            return '';
        }

        return (string) $value;
    }

    /**
     * Split one GET parameter into raw tokens. Both "a,b" strings and
     * ["a","b"] arrays are accepted; nested arrays (e.g. categories[a][b]=x)
     * are dropped silently; null means the parameter was absent.
     *
     * @return array{0:string[], 1:bool} [tokens, wasArray]
     */
    private function tokensFromParam($value): array
    {
        if ($value === null) {
            return [[], false];
        }

        $tokens = [];
        foreach ((array) $value as $piece) {
            if (is_array($piece)) {
                continue; // nested array → ignore gracefully
            }
            foreach (explode(',', (string) $piece) as $token) {
                $tokens[] = $token;
            }
        }

        return [$tokens, is_array($value)];
    }

    /**
     * Resolve raw tokens (slugs or numeric ids) against the database
     * (parameterized lookups) into a canonical selection: existing entries
     * only, deduplicated and sorted ascending. Unknown tokens are dropped.
     *
     * @return array{slugs:string[], ids:int[], inOrder:string[]}
     */
    private function resolveGroup(array $tokens, $model): array
    {
        $tokens = array_slice(array_values(array_filter(
            array_map(static fn ($t) => trim((string) $t), $tokens),
            static fn ($t) => $t !== ''
        )), 0, self::MAX_SELECTED_PER_GROUP);

        $slugTokens = [];
        $idTokens   = [];
        foreach ($tokens as $token) {
            if (ctype_digit($token)) {
                $idTokens[] = (int) $token;
            } elseif (mb_strlen($token) <= self::MAX_TOKEN_LENGTH) {
                $slugTokens[] = $token;
            }
        }

        $slugToId = [];
        if ($slugTokens !== [] || $idTokens !== []) {
            $builder = $model->builder();
            if ($slugTokens !== []) {
                $builder->orWhereIn('slug', array_values(array_unique($slugTokens)));
            }
            if ($idTokens !== []) {
                $builder->orWhereIn('id', array_values(array_unique($idTokens)));
            }
            foreach ($builder->get()->getResultArray() as $row) {
                $slugToId[$row['slug']] = (int) $row['id'];
            }
        }

        $idToSlug = [];
        foreach ($slugToId as $slug => $id) {
            $idToSlug[$id] = $slug;
        }

        // Valid slugs in the visitor's incoming order (used to decide
        // whether a normalization redirect is needed).
        $inOrder = [];
        foreach ($tokens as $token) {
            if (ctype_digit($token)) {
                if (isset($idToSlug[(int) $token])) {
                    $inOrder[] = $idToSlug[(int) $token];
                }
            } elseif (isset($slugToId[$token])) {
                $inOrder[] = $token;
            }
        }

        $slugs = array_values(array_unique($inOrder));
        sort($slugs, SORT_STRING);

        $ids = [];
        foreach ($slugs as $slug) {
            $ids[] = $slugToId[$slug];
        }

        return ['slugs' => $slugs, 'ids' => $ids, 'inOrder' => $inOrder];
    }

    /**
     * THE canonical blog URL builder — every link (chips, pagination,
     * resets, redirects) and the JSON canonicalUrl go through this.
     * Slugs are deduplicated + sorted, properly encoded, q only when
     * non-empty, page only when > 1.
     */
    private function blogUrl(array $categorySlugs, array $tagSlugs, string $q = '', ?int $page = null): string
    {
        $categorySlugs = array_values(array_unique($categorySlugs));
        sort($categorySlugs, SORT_STRING);
        $tagSlugs = array_values(array_unique($tagSlugs));
        sort($tagSlugs, SORT_STRING);

        $parts = [];
        if ($categorySlugs !== []) {
            $parts[] = 'categories=' . implode(',', array_map('rawurlencode', $categorySlugs));
        }
        if ($tagSlugs !== []) {
            $parts[] = 'tags=' . implode(',', array_map('rawurlencode', $tagSlugs));
        }
        if ($q !== '') {
            $parts[] = 'q=' . rawurlencode($q);
        }
        if ($page !== null && $page > 1) {
            $parts[] = 'page=' . $page;
        }

        return $parts === [] ? base_url('blog') : base_url('blog') . '?' . implode('&', $parts);
    }

    // ------------------------------------------------------------------
    // Shared listing data (full page + results endpoint)
    // ------------------------------------------------------------------

    /**
     * Run the filtered, paginated query and assemble everything both the
     * full page and the partial results view need. Public visibility rules
     * live inside the model queries and apply to every branch.
     *
     * @param array{q:string, categoryIds:int[], tagIds:int[], page:int} $parsed
     */
    private function listingData(array $parsed): array
    {
        $categoryModel = new CategoryModel();
        $tagModel      = new TagModel();

        $filters = [
            'q'            => $parsed['q'],
            'category_ids' => $parsed['categoryIds'],
            'tag_ids'      => $parsed['tagIds'],
        ];

        // ----- Server-side pagination over the DISTINCT article count -----
        $page     = $parsed['page'];
        $result   = $this->articleModel->publishedFiltered($filters, self::PER_PAGE, $page);
        $total    = $result['total'];
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        if ($page > $totalPages) {
            // Out-of-range page → clamp to the last existing page so every
            // URL stays reloadable and sensible.
            $page   = $totalPages;
            $result = $this->articleModel->publishedFiltered($filters, self::PER_PAGE, $page);
        }

        // Chip rows in canonical (slug-sorted) order.
        $selectedCategories = $parsed['categorySlugs'] !== []
            ? $categoryModel->whereIn('slug', $parsed['categorySlugs'])->orderBy('slug', 'ASC')->findAll()
            : [];
        $selectedTags = $parsed['tagSlugs'] !== []
            ? $tagModel->whereIn('slug', $parsed['tagSlugs'])->orderBy('slug', 'ASC')->findAll()
            : [];

        $hasFilters = ($parsed['q'] !== '' || $parsed['categorySlugs'] !== [] || $parsed['tagSlugs'] !== []);

        // ----- Sidebar data (existing records, published counts) -----
        $allCategories = $categoryModel->findAll();
        $allTags       = $tagModel->orderBy('name', 'ASC')->findAll();
        $catNames = array_column($allCategories, 'name', 'id');
        $catSlugs = array_column($allCategories, 'slug', 'id');
        $tagNames = array_column($allTags, 'name', 'id');
        $tagSlugs = array_column($allTags, 'slug', 'id');

        $sidebarCategories = [];
        foreach ($this->articleModel->publishedCategoryCounts() as $cid => $n) {
            if (isset($catNames[$cid])) {
                $sidebarCategories[] = ['id' => (int) $cid, 'name' => $catNames[$cid], 'slug' => $catSlugs[$cid], 'n' => $n];
            }
        }
        usort($sidebarCategories, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        $sidebarTags = [];
        foreach ($this->articleModel->publishedTagCounts() as $tid => $n) {
            if (isset($tagNames[$tid])) {
                $sidebarTags[] = ['id' => (int) $tid, 'name' => $tagNames[$tid], 'slug' => $tagSlugs[$tid], 'n' => $n];
            }
        }
        usort($sidebarTags, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        // The same "Tata Letak Kartu" control in Pengaturan Blog drives the
        // desktop column count (2 or 3) here AND on the homepage section;
        // small screens collapse to one column on both pages.
        $sectionModel = new HomepageSectionModel();
        $blogSection  = $sectionModel->findSection('blog');
        $blog         = $blogSection ? json_decode($blogSection['content'], true) : [];
        $cardLayout   = ($blog['card_layout'] ?? 'grid_3_columns') === 'grid_2_columns' ? 'grid_2_columns' : 'grid_3_columns';

        return [
            'blogTitle'          => $blogSection['title'] ?? 'Blog',
            'articles'           => $result['articles'],
            'total'              => $total,
            'page'               => $page,
            'totalPages'         => $totalPages,
            'perPage'            => self::PER_PAGE,
            'q'                  => $parsed['q'],
            'categorySlugs'      => $parsed['categorySlugs'],
            'tagSlugs'           => $parsed['tagSlugs'],
            'selectedCategories' => $selectedCategories,
            'selectedTags'       => $selectedTags,
            'hasFilters'         => $hasFilters,
            'sidebarCategories'  => $sidebarCategories,
            'sidebarTags'        => $sidebarTags,
            'catNames'           => $catNames,
            'articleCategoryIds' => $this->articleModel->categoryIdsGroupedByArticle(),
            'cardLayout'         => $cardLayout,
            // Canonical builder handed to the views as a closure.
            'blogUrl' => fn (array $categorySlugs, array $tagSlugs, string $q = '', ?int $page = null): string
                => $this->blogUrl($categorySlugs, $tagSlugs, $q, $page),
        ];
    }

    protected function identity(): array
    {
        $settingModel = new SettingModel();

        return $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');
    }
}
