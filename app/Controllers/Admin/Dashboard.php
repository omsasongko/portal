<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Models\HomepageSectionModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $settingModel  = new SettingModel();
        $sectionModel  = new HomepageSectionModel();

        return view('admin/dashboard', [
            'title'           => 'Dasbor Admin',
            'settingsCount'   => $settingModel->countAll(),
            'heroCount'       => $sectionModel->where('is_enabled', 1)->countAllResults(),
        ]);
    }
}
