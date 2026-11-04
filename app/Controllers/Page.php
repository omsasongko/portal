<?php

namespace App\Controllers;

use App\Models\PageModel;
use App\Models\SettingModel;

class Page extends BaseController
{
    public function show(string $slug)
    {
        helper('menu');

        $pageModel = new PageModel();
        $page = $pageModel->findPublishedBySlug($slug);

        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $settingModel = new SettingModel();
        $identity = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');

        // Homepage-qualified section links (/#anchor) on standalone pages.
        $menuItems = build_public_menu(true);

        return view('portal/page', [
            'page'      => $page,
            'identity'  => $identity,
            'menuItems' => $menuItems,
        ]);
    }
}
