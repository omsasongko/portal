<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuItemModel extends Model
{
    protected $table         = 'menu_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['label', 'type', 'target', 'sort_order', 'is_visible'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getOrdered(): array
    {
        return $this->orderBy('sort_order', 'ASC')->findAll();
    }

    public function getNextSortOrder(): int
    {
        $max = $this->selectMax('sort_order', 'max_sort')->first();
        return ($max['max_sort'] ?? -1) + 1;
    }
}
