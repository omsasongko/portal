<?php

namespace App\Controllers;

use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\TagModel;
use App\Models\SettingModel;

class Blog extends BaseController
{
    protected ArticleModel $articleModel;

    public function __construct()
    {
        helper('menu');
        $this->articleModel = new ArticleModel();
    }

    /**
     * Public paginated article listing at /blog.
     */
    public function index()
    {
        $perPage  = 6;
        $articles = $this->articleModel->publishedPaginated($perPage);
        $pager    = $this->articleModel->pager;

        $categoryModel = new CategoryModel();
        $catNames      = array_column($categoryModel->findAll(), 'name', 'id');

        return view('portal/blog_list', [
            'articles'  => $articles,
            'pager'     => $pager,
            'perPage'   => $perPage,
            'catNames'  => $catNames,
            'identity'  => $this->identity(),
            'menuItems' => build_public_menu(true),
        ]);
    }

    /**
     * Public article detail at /blog/{slug}. Drafts, deleted, and missing
     * articles are not publicly accessible (404).
     */
    public function show(string $slug)
    {
        $article = $this->articleModel->findPublishedBySlug($slug);

        if (! $article) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Artikel tidak tersedia');
        }

        $categoryModel = new CategoryModel();
        $tagModel      = new TagModel();

        $category = $article['category_id'] ? $categoryModel->find((int) $article['category_id']) : null;
        $tagIds   = $this->articleModel->tagIdsForArticle((int) $article['id']);
        $tags     = $tagIds ? $tagModel->whereIn('id', $tagIds)->findAll() : [];

        $related = ($article['detail_layout'] === 'sidebar')
            ? $this->articleModel->relatedPublished((int) $article['id'], $article['category_id'] ? (int) $article['category_id'] : null)
            : [];

        return view('portal/blog_detail', [
            'article'   => $article,
            'category'  => $category,
            'tags'      => $tags,
            'related'   => $related,
            'identity'  => $this->identity(),
            'menuItems' => build_public_menu(true),
            'isPreview' => false,
        ]);
    }

    protected function identity(): array
    {
        $settingModel = new SettingModel();

        return $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');
    }
}
