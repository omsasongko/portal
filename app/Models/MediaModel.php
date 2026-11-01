<?php

namespace App\Models;

use CodeIgniter\Model;

class MediaModel extends Model
{
    protected $table         = 'media';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['title', 'filename', 'mime_type'];
    protected $useTimestamps = false;
}
