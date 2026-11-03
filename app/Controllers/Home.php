<?php

namespace App\Controllers;

use App\Models\SettingModel;
use App\Models\HomepageSectionModel;
use App\Models\HomepageSectionItemModel;
use App\Models\PortfolioCategoryModel;
use App\Models\PortfolioItemModel;
use App\Models\PortfolioItemCategoryModel;
use App\Models\MenuItemModel;
use App\Models\PageModel;

class Home extends BaseController
{
    public function index(): string
    {
        $settingModel = new SettingModel();
        $sectionModel = new HomepageSectionModel();
        $itemModel    = new HomepageSectionItemModel();
        $portfolioCategoryModel     = new PortfolioCategoryModel();
        $portfolioItemModel         = new PortfolioItemModel();
        $portfolioItemCategoryModel = new PortfolioItemCategoryModel();
        $menuModel = new MenuItemModel();
        $pageModel = new PageModel();

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

        $portfolioSection = $sectionModel->findSection('portfolio');
        $portfolio        = $portfolioSection ? json_decode($portfolioSection['content'], true) : [];
        $portfolioEnabled = $portfolioSection ? (bool) $portfolioSection['is_enabled'] : false;
        $portfolioCategories = [];
        $portfolioItems = [];
        if ($portfolioSection) {
            $portfolioCategories = $portfolioCategoryModel->orderBy('position', 'ASC')->findAll();
            $portfolioItems = $portfolioItemModel->orderBy('position', 'ASC')->findAll();
            foreach ($portfolioItems as $i => $item) {
                $portfolioItems[$i]['category_ids'] = $portfolioItemCategoryModel->categoryIdsForItem((int) $item['id']);
            }
        }

        $testimonialsSection = $sectionModel->findSection('testimonials');
        $testimonials        = $testimonialsSection ? json_decode($testimonialsSection['content'], true) : [];
        $testimonialsEnabled = $testimonialsSection ? (bool) $testimonialsSection['is_enabled'] : false;
        $testimonialItems    = [];
        if ($testimonialsSection) {
            foreach ($itemModel->findBySection((int) $testimonialsSection['id']) as $item) {
                $testimonialItems[] = json_decode($item['content'], true) ?: [];
            }
        }

        // Build dynamic navigation menu
        $menuItems = $this->buildPublicMenu($menuModel, $sectionModel, $pageModel);

        return view('portal/home', [
            'identity'            => $identity,
            'hero'                => $hero,
            'about'               => $about,
            'aboutEnabled'        => $aboutEnabled,
            'services'            => $services,
            'servicesEnabled'     => $servicesEnabled,
            'servicesItems'       => $servicesItems,
            'portfolio'           => $portfolio,
            'portfolioEnabled'    => $portfolioEnabled,
            'portfolioCategories' => $portfolioCategories,
            'portfolioItems'      => $portfolioItems,
            'testimonials'        => $testimonials,
            'testimonialsEnabled' => $testimonialsEnabled,
            'testimonialItems'    => $testimonialItems,
            'menuItems'           => $menuItems,
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
        // home, blog, contact are always-present sections (no database toggle)
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
                $item['url'] = '#' . $sectionKey;
                $item['is_section'] = true;
            } elseif ($item['type'] === 'page') {
                $page = $pageModel->findPublishedBySlug($item['target']);
                if (! $page) {
                    continue;
                }
                $item['url'] = base_url('pages/' . $item['target']);
                $item['is_section'] = false;
            } elseif ($item['type'] === 'url') {
                $item['url'] = $item['target'];
                $item['is_section'] = false;
            }

            $result[] = $item;
        }

        return $result;
    }
}
