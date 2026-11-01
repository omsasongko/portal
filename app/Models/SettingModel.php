<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table         = 'site_settings';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['group', 'key', 'value'];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getValue(string $key, string $group = 'site_identity', $default = null)
    {
        $row = $this->where('group', $group)->where('key', $key)->first();

        return $row ? $row['value'] : $default;
    }

    public function setValue(string $key, string $value, string $group = 'site_identity'): void
    {
        $exists = $this->where('group', $group)->where('key', $key)->first();

        if ($exists) {
            $this->update($exists['id'], ['value' => $value]);
        } else {
            $this->insert(['group' => $group, 'key' => $key, 'value' => $value]);
        }
    }

    public function getGroup(string $group): array
    {
        $rows = $this->where('group', $group)->findAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row['value'];
        }

        return $result;
    }
}
