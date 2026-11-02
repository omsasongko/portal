<?php

namespace App\Models;

use CodeIgniter\Model;

class PortfolioItemModel extends Model
{
    protected $table         = 'portfolio_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['image', 'image_alt', 'title', 'link', 'position'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function maxPosition(): int
    {
        $row = $this->orderBy('position', 'DESC')->first();

        return $row ? (int) $row['position'] : 0;
    }
}