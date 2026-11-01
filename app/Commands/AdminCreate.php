<?php

namespace App\Commands;

use App\Models\AdminModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AdminCreate extends BaseCommand
{
    protected $group       = 'Admin';
    protected $name        = 'admin:create';
    protected $description = 'Membuat akun administrator baru.';
    protected $usage       = 'admin:create';

    public function run(array $params)
    {
        $model = new AdminModel();

        $name  = CLI::prompt('Nama lengkap');
        $email = CLI::prompt('Email');

        do {
            $username = CLI::prompt('Username');
            if (empty($username) || ! preg_match('/^[a-zA-Z0-9_-]{3,100}$/', $username)) {
                CLI::error('Username minimal 3 karakter, hanya huruf, angka, underscore, dan dash.');
                $username = null;
                continue;
            }
            if ($model->findByUsername($username)) {
                CLI::error('Username sudah digunakan.');
                $username = null;
            }
        } while (empty($username));

        if ($model->findByEmail($email)) {
            CLI::error('Email sudah digunakan.');
            return;
        }

        $password = $this->promptPassword('Kata sandi');
        if (strlen($password) < 8) {
            CLI::error('Kata sandi minimal 8 karakter.');
            return;
        }

        $confirm = $this->promptPassword('Ulangi kata sandi');
        if ($password !== $confirm) {
            CLI::error('Kata sandi tidak cocok.');
            return;
        }

        $data = [
            'name'          => $name,
            'email'         => $email,
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'active'        => 1,
        ];

        if (! $model->insert($data)) {
            foreach ($model->errors() as $field => $error) {
                CLI::error("{$field}: {$error}");
            }
            return;
        }

        CLI::write("Administrator '{$username}' berhasil dibuat.", 'green');
    }

    private function promptPassword(string $label): string
    {
        CLI::write($label . ': ', false);
        if (DIRECTORY_SEPARATOR === '/') {
            shell_exec('stty -echo');
        }
        $password = fgets(STDIN) ?: '';
        if (DIRECTORY_SEPARATOR === '/') {
            shell_exec('stty echo');
        }
        CLI::write('');

        return trim($password);
    }
}
