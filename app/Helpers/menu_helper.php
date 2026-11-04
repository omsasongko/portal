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

        $result = [];
        foreach ($menuModel->getOrdered() as $item) {
            if (! $item['is_visible']) {
                continue;
            }

            if ($item['type'] === 'section') {
                if (! ($sectionMap[$item['target']] ?? false)) {
                    continue;
                }
                $item['url']        = $prefix . '#' . $item['target'];
                $item['is_section'] = true;
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

            $result[] = $item;
        }

        return $result;
    }
}
