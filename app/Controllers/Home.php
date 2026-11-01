<?php

namespace App\Controllers;

use App\Models\SettingModel;
use App\Models\HomepageSectionModel;

class Home extends BaseController
{
    public function index(): string
    {
        $settingModel = new SettingModel();
        $sectionModel = new HomepageSectionModel();

        $identity = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');

        $heroSection = $sectionModel->findSection('hero');
        $hero        = $heroSection ? json_decode($heroSection['content'], true) : [];

        return view('portal/home', [
            'identity' => $identity,
            'hero'     => $hero,
        ]);
    }
}
