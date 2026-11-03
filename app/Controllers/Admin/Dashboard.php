<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Models\MediaModel;
use App\Models\HomepageSectionModel;
use App\Models\CategoryModel;
use App\Models\TagModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $settingModel  = new SettingModel();
        $mediaModel    = new MediaModel();
        $sectionModel  = new HomepageSectionModel();
        $categoryModel = new CategoryModel();
        $tagModel      = new TagModel();

        $enabledSectionsCount = $sectionModel->where('is_enabled', 1)->countAllResults();

        return view('admin/dashboard', [
            'title'                => 'Dasbor',
            'settingsCount'        => $settingModel->countAll(),
            'heroCount'            => $enabledSectionsCount,
            'mediaCount'           => $mediaModel->countAll(),
            'categoriesCount'      => $categoryModel->countAll(),
            'tagsCount'            => $tagModel->countAll(),
            'sectionsCount'        => $sectionModel->countAll(),
            'enabledSectionsCount' => $enabledSectionsCount,
        ]);
    }
}
