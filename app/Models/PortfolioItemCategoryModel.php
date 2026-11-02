<?php

namespace App\Models;

use CodeIgniter\Model;

class PortfolioItemCategoryModel extends Model
{
    protected $table           = 'portfolio_item_categories';
    protected $primaryKey      = 'item_id';
    protected $useAutoIncrement = false;
    protected $returnType      = 'array';
    protected $allowedFields   = ['item_id', 'category_id'];
    protected $useTimestamps   = false;
    protected $useSoftDeletes  = false;

    public function categoryIdsForItem(int $itemId): array
    {
        $rows = $this->where('item_id', $itemId)->findAll();

        return array_map(fn ($row) => (int) $row['category_id'], $rows);
    }

    public function syncForItem(int $itemId, array $categoryIds): void
    {
        $this->where('item_id', $itemId)->delete();

        foreach (array_values(array_unique($categoryIds)) as $categoryId) {
            $this->insert([
                'item_id'     => $itemId,
                'category_id' => (int) $categoryId,
            ]);
        }
    }

    public function countByCategory(int $categoryId): int
    {
        return $this->where('category_id', $categoryId)->countAllResults();
    }
}