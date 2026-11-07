<?php

use App\Models\MenuItemModel;
use App\Models\HomepageSectionModel;
use App\Models\PageModel;

if (! function_exists('build_public_menu')) {
    /**
     * Build the public navigation menu from saved menu_items.
     *
     * Auto-omits: hidden items (is_visible = 0), links to disabled homepage
     * sections, links to unpublished/deleted pages. The article listing item
     * is always kept (it has its own public route).
     *
     * @param bool $homepageQualified When true, section links are prefixed with
     *                                the homepage URL (e.g. "/#about") so they
     *                                work from standalone pages.
     */
    function build_public_menu(bool $homepageQualified = false): array
    {
        $menuModel    = new MenuItemModel();
        $sectionModel = new HomepageSectionModel();
        $pageModel    = new PageModel();

        $sectionMap = [];
        foreach ($sectionModel->findAll() as $s) {
            $sectionMap[$s['section']] = (bool) $s['is_enabled'];
        }
        // home and contact have no database toggle row; they always render.
        $sectionMap['home']    = true;
        $sectionMap['contact'] = true;

        $prefix = $homepageQualified ? rtrim(base_url('/'), '/') . '/' : '';

        // Current route path (relative to baseURL, no leading/trailing slash)
        // used to mark the matching menu item active on standalone pages
        // (the homepage relies on ScrollSpy instead, where path is empty).
        $currentPath = '';
        try {
            $currentPath = trim((string) service('uri')->getPath(), '/');
        } catch (\Throwable $e) {
            $currentPath = '';
        }

        $result = [];
        foreach ($menuModel->getOrdered() as $item) {
            if (! $item['is_visible']) {
                continue;
            }

            if ($item['type'] === 'section') {
                if (! ($sectionMap[$item['target']] ?? false)) {
                    continue;
                }
                if ($item['target'] === 'blog') {
                    // The BLOG entry always opens the full public article
                    // listing (its own route), not the homepage anchor, so the
                    // main navigation reaches every article and pagination.
                    $item['url']        = base_url('blog');
                    $item['is_section'] = false;
                } else {
                    $item['url']        = $prefix . '#' . $item['target'];
                    $item['is_section'] = true;
                }
            } elseif ($item['type'] === 'page') {
                if (! $pageModel->findPublishedBySlug($item['target'])) {
                    continue;
                }
                $item['url']        = base_url('pages/' . $item['target']);
                $item['is_section'] = false;
            } elseif ($item['type'] === 'articles') {
                $item['url']        = base_url('blog');
                $item['is_section'] = false;
            } elseif ($item['type'] === 'url') {
                $item['url']        = $item['target'];
                $item['is_section'] = false;
            } else {
                continue;
            }

            // Mark this item active when its destination matches the current
            // page. Section items (home/about/portfolio/contact) are left to
            // ScrollSpy on the homepage and are never force-marked here.
            $item['active'] = false;
            if ($item['type'] === 'page') {
                $item['active'] = $currentPath === 'pages/' . $item['target'];
            } elseif (($item['type'] === 'articles')
                || ($item['type'] === 'section' && $item['target'] === 'blog')) {
                $item['active'] = $currentPath === 'blog' || str_starts_with($currentPath, 'blog/');
            }

            $result[] = $item;
        }

        return $result;
    }
}
