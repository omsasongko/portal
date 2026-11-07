<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactMessageModel extends Model
{
    protected $table         = 'contact_messages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'name', 'email', 'phone', 'message',
        'is_read', 'ip_address',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Count unread messages.
     */
    public function countUnread(): int
    {
        return (int) $this->where('is_read', 0)->countAllResults();
    }
}
