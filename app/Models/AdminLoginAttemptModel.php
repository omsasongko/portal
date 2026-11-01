<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminLoginAttemptModel extends Model
{
    protected $table         = 'admin_login_attempts';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['username', 'ip_address', 'attempted_at'];
    protected $useTimestamps = false;

    public function countRecent(string $username, string $ip, int $minutes = 15): int
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        return $this
            ->where('username', $username)
            ->where('ip_address', $ip)
            ->where('attempted_at >=', $since)
            ->countAllResults();
    }

    public function record(string $username, string $ip): void
    {
        $this->insert([
            'username'    => $username,
            'ip_address'    => $ip,
            'attempted_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function clear(string $username, string $ip): void
    {
        $this->where('username', $username)
            ->where('ip_address', $ip)
            ->delete();
    }
}
