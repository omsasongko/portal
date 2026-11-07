<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Models\MediaModel;
use App\Models\HomepageSectionModel;
use App\Models\CategoryModel;
use App\Models\TagModel;
use App\Models\PageModel;
use App\Models\MenuItemModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $settingModel  = new SettingModel();
        $identity      = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');
        $mediaModel    = new MediaModel();
        $sectionModel  = new HomepageSectionModel();
        $categoryModel = new CategoryModel();
        $tagModel      = new TagModel();
        $pageModel     = new PageModel();
        $menuModel     = new MenuItemModel();

        $enabledSectionsCount = $sectionModel->where('is_enabled', 1)->countAllResults();

        return view('admin/dashboard', [
            'title'                => 'Dasbor',
            'identity'             => $identity,
            'settingsCount'        => $settingModel->countAll(),
            'heroCount'            => $enabledSectionsCount,
            'mediaCount'           => $mediaModel->countAll(),
            'categoriesCount'      => $categoryModel->countAll(),
            'tagsCount'            => $tagModel->countAll(),
            'sectionsCount'        => $sectionModel->countAll(),
            'enabledSectionsCount' => $enabledSectionsCount,
            'pagesCount'           => $pageModel->countAll(),
            'menuCount'            => $menuModel->countAll(),
        ]);
    }
}
