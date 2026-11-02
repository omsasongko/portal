<?php

namespace App\Controllers;

use App\Models\SettingModel;
use App\Models\HomepageSectionModel;
use App\Models\HomepageSectionItemModel;

class Home extends BaseController
{
    public function index(): string
    {
        $settingModel = new SettingModel();
        $sectionModel = new HomepageSectionModel();
        $itemModel    = new HomepageSectionItemModel();

        $identity = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');

        $heroSection = $sectionModel->findSection('hero');
        $hero        = $heroSection ? json_decode($heroSection['content'], true) : [];

        $aboutSection = $sectionModel->findSection('about');
        $about        = $aboutSection ? json_decode($aboutSection['content'], true) : [];
        $aboutEnabled = $aboutSection ? (bool) $aboutSection['is_enabled'] : false;

        $servicesSection = $sectionModel->findSection('services');
        $services        = $servicesSection ? json_decode($servicesSection['content'], true) : [];
        $servicesEnabled = $servicesSection ? (bool) $servicesSection['is_enabled'] : false;
        $servicesItems   = [];
        if ($servicesSection) {
            foreach ($itemModel->findBySection((int) $servicesSection['id']) as $item) {
                $servicesItems[] = json_decode($item['content'], true) ?: [];
            }
        }

        return view('portal/home', [
            'identity'        => $identity,
            'hero'            => $hero,
            'about'           => $about,
            'aboutEnabled'    => $aboutEnabled,
            'services'        => $services,
            'servicesEnabled' => $servicesEnabled,
            'servicesItems'   => $servicesItems,
        ]);
    }
}
