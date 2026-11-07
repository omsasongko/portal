<?php

namespace App\Models;

use CodeIgniter\Model;

class ArticleModel extends Model
{
    protected $table         = 'articles';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'title', 'slug', 'excerpt', 'body', 'cover_image', 'cover_alt',
        'category_id', 'detail_layout', 'status', 'published_at',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->first() ?: null;
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->where('status', 'published')->first() ?: null;
    }

    /**
     * Latest published articles (newest published_at first), optionally limited.
     */
    public function latestPublished(?int $limit = null): array
    {
        $builder = $this->where('status', 'published')
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC');

        if ($limit !== null && $limit > 0) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Paginated published articles, newest first.
     */
    public function publishedPaginated(int $perPage = 6): array
    {
        return $this->where('status', 'published')
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate($perPage);
    }

    /**
     * Public listing query with OPTIONAL combined filters: full-text search
     * (title/excerpt/body), a set of category ids and/or a set of tag ids.
     * Within a group ANY selected id matches (OR); the groups and the search
     * term combine with AND. An empty group applies no restriction. Public
     * visibility rules (status = published) apply to every combination.
     * Joins can match one article several times (multi-category/tag), so the
     * page uses GROUP BY and the total is COUNT(DISTINCT a.id): every article
     * is counted and shown exactly once. Sorted published_at DESC, id DESC.
     *
     * @param array{q?:string, category_ids?:int[], tag_ids?:int[]} $filters
     *
     * @return array{articles: array<int, array>, total: int}
     */
    public function publishedFiltered(array $filters, int $perPage, int $page): array
    {
        $base = function () use ($filters) {
            $builder = $this->db->table('articles a')
                ->where('a.status', 'published');

            $q = trim((string) ($filters['q'] ?? ''));
            if ($q !== '') {
                $builder->groupStart()
                    ->like('a.title', $q)
                    ->orLike('a.excerpt', $q)
                    ->orLike('a.body', $q)
                    ->groupEnd();
            }

            // ANY of the selected categories (parameterized via whereIn).
            $categoryIds = array_values(array_unique(array_filter(array_map('intval', (array) ($filters['category_ids'] ?? [])))));
            if ($categoryIds !== []) {
                $builder->join('article_categories ac', 'ac.article_id = a.id', 'inner')
                    ->whereIn('ac.category_id', $categoryIds);
            }

            // ANY of the selected tags.
            $tagIds = array_values(array_unique(array_filter(array_map('intval', (array) ($filters['tag_ids'] ?? [])))));
            if ($tagIds !== []) {
                $builder->join('article_tags atg', 'atg.article_id = a.id', 'inner')
                    ->whereIn('atg.tag_id', $tagIds);
            }

            return $builder;
        };

        $total = (int) $base()
            ->select('COUNT(DISTINCT a.id) AS n', false)
            ->get()
            ->getRow()
            ->n;

        $articles = $base()
            ->select('a.*')
            ->groupBy('a.id')
            ->orderBy('a.published_at', 'DESC')
            ->orderBy('a.id', 'DESC')
            ->limit(max(1, $perPage), (max(1, $page) - 1) * max(1, $perPage))
            ->get()
            ->getResultArray();

        return ['articles' => $articles, 'total' => $total];
    }

    /**
     * Published-article count per category (each article once), for the
     * listing sidebar. Only categories with at least one match are returned.
     *
     * @return array<int|string, int> category_id => count
     */
    public function publishedCategoryCounts(): array
    {
        $rows = $this->db->table('article_categories ac')
            ->select('ac.category_id AS id, COUNT(DISTINCT ac.article_id) AS n', false)
            ->join('articles a', 'a.id = ac.article_id', 'inner')
            ->where('a.status', 'published')
            ->groupBy('ac.category_id')
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'n', 'id'));
    }

    /**
     * Published-article count per tag (each article once), same as
     * publishedCategoryCounts() for tags.
     *
     * @return array<int|string, int> tag_id => count
     */
    public function publishedTagCounts(): array
    {
        $rows = $this->db->table('article_tags atg')
            ->select('atg.tag_id AS id, COUNT(DISTINCT atg.article_id) AS n', false)
            ->join('articles a', 'a.id = atg.article_id', 'inner')
            ->where('a.status', 'published')
            ->groupBy('atg.tag_id')
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'n', 'id'));
    }

    /**
     * Published articles sharing ANY of the given categories, excluding the
     * given article. With no category ids (or for uncategorised articles) any
     * published article qualifies. A single query over articles keeps the
     * result free of duplicates even when an article matches several of the
     * selected categories.
     */
    public function relatedPublished(int $articleId, array $categoryIds, int $limit = 3): array
    {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));

        $builder = $this->where('status', 'published')
            ->where('id !=', $articleId)
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC');

        if ($categoryIds !== []) {
            $builder->whereIn('id', function ($sub) use ($categoryIds) {
                $sub->select('article_id')
                    ->from('article_categories')
                    ->whereIn('category_id', $categoryIds);
            });
        }

        $related = $builder->limit($limit)->findAll();

        if (count($related) >= $limit) {
            return $related;
        }

        // Fill remaining slots with other published articles.
        $ids = array_column($related, 'id');
        $ids[] = $articleId;
        $others = $this->where('status', 'published')
            ->whereNotIn('id', $ids)
            ->orderBy('published_at', 'DESC')
            ->limit($limit - count($related))
            ->findAll();

        return array_merge($related, $others);
    }

    /**
     * Tag IDs linked to an article.
     */
    public function tagIdsForArticle(int $articleId): array
    {
        return array_map(
            'intval',
            array_column(
                $this->db->table('article_tags')->where('article_id', $articleId)->get()->getResultArray(),
                'tag_id'
            )
        );
    }

    /**
     * Replace tag links for an article.
     *
     * @param int[] $tagIds
     */
    public function syncTags(int $articleId, array $tagIds): void
    {
        $this->db->table('article_tags')->where('article_id', $articleId)->delete();

        $rows = [];
        foreach (array_unique(array_map('intval', $tagIds)) as $tagId) {
            $rows[] = ['article_id' => $articleId, 'tag_id' => $tagId];
        }

        if ($rows !== []) {
            $this->db->table('article_tags')->insertBatch($rows);
        }
    }

    /**
     * Category IDs linked to an article (same pattern as tagIdsForArticle).
     */
    public function categoryIdsForArticle(int $articleId): array
    {
        return array_map(
            'intval',
            array_column(
                $this->db->table('article_categories')->where('article_id', $articleId)->get()->getResultArray(),
                'category_id'
            )
        );
    }

    /**
     * Map of article_id => category IDs for many articles at once (list
     * views), read from the pivot in one query.
     */
    public function categoryIdsGroupedByArticle(): array
    {
        $map = [];
        foreach ($this->db->table('article_categories')->get()->getResultArray() as $row) {
            $map[(int) $row['article_id']][] = (int) $row['category_id'];
        }

        return $map;
    }

    /**
     * Replace category links for an article (same pattern as syncTags).
     *
     * @param int[] $categoryIds
     */
    public function syncCategories(int $articleId, array $categoryIds): void
    {
        $this->db->table('article_categories')->where('article_id', $articleId)->delete();

        $rows = [];
        foreach (array_unique(array_map('intval', $categoryIds)) as $categoryId) {
            $rows[] = ['article_id' => $articleId, 'category_id' => $categoryId];
        }

        if ($rows !== []) {
            $this->db->table('article_categories')->insertBatch($rows);
        }
    }
}
