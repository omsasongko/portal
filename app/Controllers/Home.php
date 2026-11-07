<?php

namespace App\Controllers;

use App\Models\SettingModel;
use App\Models\HomepageSectionModel;
use App\Models\HomepageSectionItemModel;
use App\Models\PortfolioCategoryModel;
use App\Models\PortfolioItemModel;
use App\Models\PortfolioItemCategoryModel;
use App\Models\ArticleModel;
use App\Libraries\VisitorTracker;

class Home extends BaseController
{
    public function index(): string
    {
        helper('menu');

        $settingModel = new SettingModel();
        $sectionModel = new HomepageSectionModel();
        $itemModel    = new HomepageSectionItemModel();
        $portfolioCategoryModel     = new PortfolioCategoryModel();
        $portfolioItemModel         = new PortfolioItemModel();
        $portfolioItemCategoryModel = new PortfolioItemCategoryModel();
        $articleModel = new ArticleModel();

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

        $blogSection = $sectionModel->findSection('blog');
        $blog        = $blogSection ? json_decode($blogSection['content'], true) : [];
        $blogEnabled = $blogSection ? (bool) $blogSection['is_enabled'] : false;
        $blogArticles = [];
        if ($blogEnabled) {
            $limit = max(1, min(12, (int) ($blog['limit'] ?? 3)));
            $blogArticles = $articleModel->latestPublished($limit);
        }

        $contactSection = $sectionModel->findSection('contact');
        $contact        = $contactSection ? json_decode($contactSection['content'], true) : [];
        $contactEnabled = $contactSection ? (bool) $contactSection['is_enabled'] : true;

        // Build dynamic navigation menu (shared helper; section links are
        // plain "#anchor" on the homepage for the smooth-scroll behavior).
        $menuItems = build_public_menu(false);

        VisitorTracker::record('home', 0, '/');

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
            'blog'                => $blog,
            'blogEnabled'         => $blogEnabled,
            'blogArticles'        => $blogArticles,
            'contact'             => $contact,
            'contactEnabled'      => $contactEnabled,
            'menuItems'           => $menuItems,
        ]);
    }
}
