<?php

namespace App\Models;

use CodeIgniter\Model;

class HomepageSectionItemModel extends Model
{
    protected $table         = 'homepage_section_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['section_id', 'position', 'content'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findBySection(int $sectionId): array
    {
        return $this->where('section_id', $sectionId)->orderBy('position', 'ASC')->findAll();
    }

    public function maxPosition(int $sectionId): int
    {
        $row = $this->where('section_id', $sectionId)->orderBy('position', 'DESC')->first();

        return $row ? (int) $row['position'] : 0;
    }
}