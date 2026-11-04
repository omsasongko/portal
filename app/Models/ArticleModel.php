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
     * Published articles in the same category, excluding the given article.
     */
    public function relatedPublished(int $articleId, ?int $categoryId, int $limit = 3): array
    {
        if (! $categoryId) {
            return $this->where('status', 'published')
                ->where('id !=', $articleId)
                ->orderBy('published_at', 'DESC')
                ->limit($limit)
                ->findAll();
        }

        $sameCategory = $this->where('status', 'published')
            ->where('category_id', $categoryId)
            ->where('id !=', $articleId)
            ->orderBy('published_at', 'DESC')
            ->limit($limit)
            ->findAll();

        if (count($sameCategory) >= $limit) {
            return $sameCategory;
        }

        // Fill remaining slots with other published articles.
        $ids = array_column($sameCategory, 'id');
        $ids[] = $articleId;
        $others = $this->where('status', 'published')
            ->whereNotIn('id', $ids)
            ->orderBy('published_at', 'DESC')
            ->limit($limit - count($sameCategory))
            ->findAll();

        return array_merge($sameCategory, $others);
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
}
