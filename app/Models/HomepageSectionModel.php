<?php

namespace App\Models;

use CodeIgniter\Model;

class HomepageSectionModel extends Model
{
    protected $table         = 'homepage_sections';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['section', 'title', 'layout', 'is_enabled', 'sort_order', 'content'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findSection(string $section): ?array
    {
        return $this->where('section', $section)->first() ?: null;
    }
}
