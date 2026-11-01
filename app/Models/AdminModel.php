<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table            = 'admin_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'name',
        'email',
        'username',
        'password_hash',
        'active',
        'last_login',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'     => 'required|max_length[255]',
        'email'    => 'required|valid_email|max_length[255]|is_unique[admin_users.email,id,{id}]',
        'username' => 'required|alpha_dash|min_length[3]|max_length[100]|is_unique[admin_users.username,id,{id}]',
    ];

    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first() ?: null;
    }

    public function updateLastLogin(int $id): void
    {
        $this->update($id, ['last_login' => date('Y-m-d H:i:s') ?? null]);
    }
}
