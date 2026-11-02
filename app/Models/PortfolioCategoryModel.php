<?php

namespace App\Models;

use CodeIgniter\Model;

class PortfolioCategoryModel extends Model
{
    protected $table         = 'portfolio_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'filter_class', 'position'];
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