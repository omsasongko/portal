<?php

namespace App\Controllers;

use App\Models\PageModel;
use App\Models\SettingModel;
use App\Models\MenuItemModel;
use App\Models\HomepageSectionModel;

class Page extends BaseController
{
    public function show(string $slug)
    {
        $pageModel = new PageModel();
        $page = $pageModel->findPublishedBySlug($slug);

        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $settingModel = new SettingModel();
        $identity = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');

        $menuModel   = new MenuItemModel();
        $sectionModel = new HomepageSectionModel();

        $menuItems = $this->buildPublicMenu($menuModel, $sectionModel, $pageModel);

        return view('portal/page', [
            'page'      => $page,
            'identity'  => $identity,
            'menuItems' => $menuItems,
        ]);
    }

    protected function buildPublicMenu(MenuItemModel $menuModel, HomepageSectionModel $sectionModel, PageModel $pageModel): array
    {
        $items = $menuModel->getOrdered();
        $result = [];

        $sectionMap = [];
        foreach ($sectionModel->findAll() as $s) {
            $sectionMap[$s['section']] = (bool) $s['is_enabled'];
        }
        // home, blog, contact are always present sections
        $sectionMap['home']    = true;
        $sectionMap['blog']    = true;
        $sectionMap['contact'] = true;

        foreach ($items as $item) {
            if (! $item['is_visible']) {
                continue;
            }

            if ($item['type'] === 'section') {
                $sectionKey = $item['target'];
                if (! ($sectionMap[$sectionKey] ?? false)) {
                    continue;
                }
                $item['url'] = base_url('/#' . $sectionKey);
            } elseif ($item['type'] === 'page') {
                $page = $pageModel->findPublishedBySlug($item['target']);
                if (! $page) {
                    continue;
                }
                $item['url'] = base_url('pages/' . $item['target']);
            } elseif ($item['type'] === 'url') {
                $item['url'] = $item['target'];
            }

            $result[] = $item;
        }

        return $result;
    }
}
