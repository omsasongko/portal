<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Models\AdminLoginAttemptModel;
use App\Libraries\VisitorTracker;

class Auth extends BaseController
{
    protected AdminModel $adminModel;
    protected AdminLoginAttemptModel $attemptModel;

    public function __construct()
    {
        $this->adminModel   = new AdminModel();
        $this->attemptModel = new AdminLoginAttemptModel();
    }

    public function loginForm()
    {
        $session = session();

        if ($session->get('admin_id')) {
            return redirect()->to('/admin');
        }

        VisitorTracker::recordAlways('admin_login', 0, '/admin/login');

        if (! $session->get('login_gate_passed')) {
            $settingModel = new \App\Models\SettingModel();
            $identity     = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');

            return view('admin/login_gate', [
                'title'    => 'Masuk Admin',
                'identity' => $identity,
            ]);
        }

        $settingModel = new \App\Models\SettingModel();
        $identity     = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');

        return view('admin/login', [
            'title'    => 'Masuk Admin',
            'identity' => $identity,
        ]);
    }

    public function doLogin()
    {
        $session = session();

        if ($session->get('admin_id')) {
            return redirect()->to('/admin');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $ip       = $this->request->getIPAddress();

        $rules = [
            'username' => 'required|max_length[100]',
            'password' => 'required|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($this->attemptModel->countRecent($username, $ip, 15) >= 5) {
            return redirect()->back()->withInput()->with('error', 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.');
        }

        $admin = $this->adminModel->findByUsername($username);

        if (! $admin || ! $admin['active']) {
            $this->attemptModel->record($username, $ip);
            return redirect()->back()->withInput()->with('error', 'Username atau kata sandi salah.');
        }

        if (! password_verify($password, $admin['password_hash'])) {
            $this->attemptModel->record($username, $ip);
            return redirect()->back()->withInput()->with('error', 'Username atau kata sandi salah.');
        }

        $this->attemptModel->clear($username, $ip);
        $this->adminModel->updateLastLogin($admin['id']);

        $session->remove('login_gate_passed');
        $session->regenerate(true);
        $session->set([
            'admin_id'    => $admin['id'],
            'admin_name'  => $admin['name'],
            'admin_email' => $admin['email'],
        ]);

        return redirect()->to('/admin')->with('success', 'Selamat datang, ' . esc($admin['name']) . '.');
    }

    public function logout()
    {
        $session = session();
        $session->destroy();

        return redirect()->to('/admin/login')->with('success', 'Anda telah keluar.');
    }

    public function changePasswordForm()
    {
        if (! session()->get('admin_id')) {
            return redirect()->to('/admin/login');
        }

        $settingModel = new \App\Models\SettingModel();
        $identity     = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');

        return view('admin/change_password', [
            'title'    => 'Ubah Kata Sandi',
            'identity' => $identity,
        ]);
    }

    public function doChangePassword()
    {
        $session = session();
        $adminId = $session->get('admin_id');

        if (! $adminId) {
            return redirect()->to('/admin/login');
        }

        $oldPassword = $this->request->getPost('old_password');
        $newPassword = $this->request->getPost('new_password');
        $confirm     = $this->request->getPost('confirm_password');

        $rules = [
            'old_password'     => 'required',
            'new_password'     => 'required|min_length[8]|max_length[255]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $admin = $this->adminModel->find($adminId);

        if (! $admin || ! password_verify($oldPassword, $admin['password_hash'])) {
            return redirect()->back()->with('error', 'Kata sandi lama tidak cocok.');
        }

        $this->adminModel->update($admin['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        $session->regenerate(true);

        return redirect()->to('/admin')->with('success', 'Kata sandi berhasil diubah.');
    }
}
