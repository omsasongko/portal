<?php

namespace App\Controllers;

use App\Libraries\VisitorTracker;

class EntryGate extends BaseController
{
    public function pass()
    {
        VisitorTracker::recordAlways('admin_login', 0, '/lanjut/asup');

        $session = session();

        $session->set('login_gate_passed', true);
        $session->markAsTempdata('login_gate_passed', 300);

        return redirect()->to(base_url('admin/login'));
    }
}
