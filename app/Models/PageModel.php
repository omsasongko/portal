<?php

namespace App\Models;

use CodeIgniter\Model;

class PageModel extends Model
{
    protected $table         = 'pages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['title', 'slug', 'content', 'status'];
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

    public function getPublishedList(): array
    {
        return $this->where('status', 'published')->orderBy('title', 'ASC')->findAll();
    }
}
