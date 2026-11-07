<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Models\MediaModel;
use App\Models\HomepageSectionModel;
use App\Models\HomepageSectionItemModel;
use App\Models\PortfolioCategoryModel;
use App\Models\PortfolioItemModel;
use App\Models\PortfolioItemCategoryModel;

class Settings extends BaseController
{
    protected SettingModel $settingModel;
    protected MediaModel $mediaModel;
    protected HomepageSectionModel $sectionModel;
    protected HomepageSectionItemModel $itemModel;
    protected PortfolioCategoryModel $portfolioCategoryModel;
    protected PortfolioItemModel $portfolioItemModel;
    protected PortfolioItemCategoryModel $portfolioItemCategoryModel;

    protected array $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'ico'];
    protected array $imageMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'];

    protected array $serviceIcons = [
        'flaticon-seo'           => 'SEO',
        'flaticon-development'   => 'Pengembangan (Development)',
        'flaticon-process'       => 'Proses (Process)',
        'flaticon-discuss-issue' => 'Diskusi (Discuss Issue)',
        'flaticon-idea'          => 'Ide (Idea)',
        'flaticon-idea-1'        => 'Ide Alt (Idea 1)',
    ];

    public function __construct()
    {
        $this->settingModel = new SettingModel();
        $this->mediaModel   = new MediaModel();
        $this->sectionModel = new HomepageSectionModel();
        $this->itemModel    = new HomepageSectionItemModel();
        $this->portfolioCategoryModel     = new PortfolioCategoryModel();
        $this->portfolioItemModel         = new PortfolioItemModel();
        $this->portfolioItemCategoryModel = new PortfolioItemCategoryModel();
    }

    public function identity()
    {
        $settings = $this->settingModel->getGroup('site_identity')
            + $this->settingModel->getGroup('global_appearance');

        return view('admin/settings_identity', [
            'title'    => 'Pengaturan Identitas Situs',
            'settings' => $settings,
            'media'    => $this->mediaModel->findAll(),
        ]);
    }

    public function doIdentity()
    {
        $rules = [
            'site_title'         => 'required|max_length[100]',
            'site_name'          => 'required|max_length[100]',
            'meta_keywords'      => 'permit_empty|max_length[255]',
            'meta_description'   => 'permit_empty|max_length[255]',
            'meta_author'        => 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->settingModel->setValue('site_title', $this->request->getPost('site_title') ?? '', 'site_identity');
        $this->settingModel->setValue('site_name', $this->request->getPost('site_name') ?? '', 'site_identity');
        $this->settingModel->setValue('meta_keywords', $this->request->getPost('meta_keywords') ?? '', 'site_identity');
        $this->settingModel->setValue('meta_description', $this->request->getPost('meta_description') ?? '', 'site_identity');
        $this->settingModel->setValue('meta_author', $this->request->getPost('meta_author') ?? '', 'site_identity');

        $uploadErrors = [];
        foreach (['logo' => 'Logo', 'favicon' => 'Favicon', 'apple_touch_icon' => 'Apple Touch Icon'] as $field => $label) {
            $error = $this->validateImageUpload($field);
            if ($error) {
                $uploadErrors[$field] = $error;
            }
        }

        if ($uploadErrors) {
            return redirect()->back()->withInput()->with('errors', $uploadErrors);
        }

        $logoPath = $this->resolveImagePath('logo', $this->request->getPost('logo') ?: $this->request->getPost('current_logo'));
        $faviconPath = $this->resolveImagePath('favicon', $this->request->getPost('favicon') ?: $this->request->getPost('current_favicon'));
        $applePath = $this->resolveImagePath('apple_touch_icon', $this->request->getPost('apple_touch_icon') ?: $this->request->getPost('current_apple_touch_icon'));

        $this->settingModel->setValue('logo', $logoPath, 'site_identity');
        $this->settingModel->setValue('favicon', $faviconPath, 'site_identity');
        $this->settingModel->setValue('apple_touch_icon', $applePath, 'site_identity');

        $this->settingModel->setValue('show_preloader', $this->request->getPost('show_preloader') ? '1' : '0', 'global_appearance');
        $this->settingModel->setValue('show_scroll_to_top', $this->request->getPost('show_scroll_to_top') ? '1' : '0', 'global_appearance');

        return redirect()->to('/admin/settings/identity')->with('success', 'Pengaturan identitas situs berhasil disimpan.');
    }

    public function hero()
    {
        $section = $this->sectionModel->findSection('hero');
        $content = $section ? json_decode($section['content'], true) : [];

        return view('admin/settings_hero', [
            'title'   => 'Pengaturan Hero',
            'hero'    => $content,
            'media'   => $this->mediaModel->findAll(),
        ]);
    }

    public function doHero()
    {
        $rules = [
            'heading'            => 'required|max_length[255]',
            'subheading'         => 'permit_empty|max_length[1000]',
            'button_label'       => 'permit_empty|max_length[100]',
            'button_link'        => 'permit_empty|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $bgError = $this->validateImageUpload('background_image');
        if ($bgError) {
            return redirect()->back()->withInput()->with('errors', ['background_image' => $bgError]);
        }

        $backgroundPath = $this->resolveImagePath('background_image', $this->request->getPost('background_image') ?: $this->request->getPost('current_background_image'));

        $rotatingWords = $this->request->getPost('rotating_words');
        $words = [];
        if (is_string($rotatingWords)) {
            foreach (explode("\n", $rotatingWords) as $word) {
                $word = trim($word);
                if ($word !== '') {
                    $words[] = $word;
                }
            }
        }

        $buttonLink = $this->request->getPost('button_link');
        if ($buttonLink && ! $this->isSafeUrl($buttonLink)) {
            return redirect()->back()->withInput()->with('error', 'Link tombol tidak aman. Gunakan URL yang valid atau anchor.');
        }

        $content = [
            'heading'            => $this->request->getPost('heading'),
            'subheading'         => $this->request->getPost('subheading'),
            'use_rotating_words' => $this->request->getPost('use_rotating_words') ? true : false,
            'rotating_words'     => $words,
            'background_image'   => $backgroundPath,
            'button_label'       => $this->request->getPost('button_label'),
            'button_link'        => $buttonLink,
        ];

        $section = $this->sectionModel->findSection('hero');
        if ($section) {
            $this->sectionModel->update($section['id'], ['content' => json_encode($content)]);
        } else {
            $this->sectionModel->insert([
                'section'    => 'hero',
                'title'      => 'Hero',
                'layout'     => 'hero_rotating',
                'is_enabled' => 1,
                'sort_order' => 0,
                'content'    => json_encode($content),
            ]);
        }

        return redirect()->to('/admin/settings/hero')->with('success', 'Pengaturan hero berhasil disimpan.');
    }

    public function about()
    {
        $section = $this->sectionModel->findSection('about');
        $content = $section ? json_decode($section['content'], true) : [];

        return view('admin/settings_about', [
            'title'   => 'Pengaturan About',
            'about'   => $content,
            'media'   => $this->mediaModel->findAll(),
        ]);
    }

    public function doAbout()
    {
        $rules = [
            'heading'      => 'required|max_length[255]',
            'body'         => 'required|max_length[5000]',
            'button_label' => 'permit_empty|max_length[100]',
            'button_link'  => 'permit_empty|max_length[255]',
            'layout'       => 'required|in_list[image_right,image_left,text_only]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $imageError = $this->validateImageUpload('about_image');
        if ($imageError) {
            return redirect()->back()->withInput()->with('errors', ['about_image' => $imageError]);
        }

        $imagePath = $this->resolveImagePath('about_image', $this->request->getPost('about_image') ?: $this->request->getPost('current_about_image'));

        $buttonLink = $this->request->getPost('button_link');
        if ($buttonLink && ! $this->isSafeUrl($buttonLink)) {
            return redirect()->back()->withInput()->with('error', 'Link tombol tidak aman. Gunakan URL yang valid atau anchor.');
        }

        $content = [
            'heading'      => $this->request->getPost('heading'),
            'body'         => $this->request->getPost('body'),
            'image'        => $imagePath,
            'image_alt'    => $this->request->getPost('image_alt') ?? '',
            'button_label' => $this->request->getPost('button_label'),
            'button_link'  => $buttonLink,
            'layout'       => $this->request->getPost('layout'),
            'is_enabled'   => $this->request->getPost('is_enabled') ? true : false,
        ];

        $section = $this->sectionModel->findSection('about');
        if ($section) {
            $this->sectionModel->update($section['id'], [
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
                'layout'     => $content['layout'],
                'is_enabled' => $content['is_enabled'] ? 1 : 0,
            ]);
        } else {
            $this->sectionModel->insert([
                'section'    => 'about',
                'title'      => 'About',
                'layout'     => $content['layout'],
                'is_enabled' => $content['is_enabled'] ? 1 : 0,
                'sort_order' => 1,
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
            ]);
        }

        return redirect()->to('/admin/settings/about')->with('success', 'Pengaturan About berhasil disimpan.');
    }

    public function services()
    {
        $section = $this->sectionModel->findSection('services');
        $content = $section ? json_decode($section['content'], true) : [];

        $items = [];
        if ($section) {
            foreach ($this->itemModel->findBySection((int) $section['id']) as $item) {
                $item['data'] = json_decode($item['content'], true) ?: [];
                $items[]      = $item;
            }
        }

        $edit = null;
        $editId = $this->request->getGet('edit');
        if ($section && $editId && is_numeric($editId)) {
            $editRow = $this->itemModel->find((int) $editId);
            if ($editRow && (int) $editRow['section_id'] === (int) $section['id']) {
                $data     = json_decode($editRow['content'], true) ?: [];
                $edit     = [
                    'id'          => (int) $editRow['id'],
                    'icon'        => $data['icon'] ?? '',
                    'title'       => $data['title'] ?? '',
                    'description' => $data['description'] ?? '',
                ];
            }
        }

        return view('admin/settings_services', [
            'title'           => 'Pengaturan Layanan',
            'services'        => $content,
            'items'           => $items,
            'icons'           => $this->serviceIcons,
            'edit'            => $edit,
            'sectionEnabled'  => $section ? (bool) $section['is_enabled'] : true,
        ]);
    }

    public function doServices()
    {
        $rules = [
            'title'    => 'required|max_length[255]',
            'subtitle' => 'permit_empty|max_length[1000]',
            'layout'   => 'required|in_list[grid_3_columns,grid_2_columns]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $content = [
            'title'    => $this->request->getPost('title'),
            'subtitle' => $this->request->getPost('subtitle') ?? '',
            'layout'   => $this->request->getPost('layout'),
        ];
        $isEnabled = $this->request->getPost('is_enabled') ? 1 : 0;

        $section = $this->sectionModel->findSection('services');
        if ($section) {
            $this->sectionModel->update($section['id'], [
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
                'layout'     => $content['layout'],
                'is_enabled' => $isEnabled,
            ]);
        } else {
            $this->sectionModel->insert([
                'section'    => 'services',
                'title'      => 'Services',
                'layout'     => $content['layout'],
                'is_enabled' => $isEnabled,
                'sort_order' => 2,
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
            ]);
        }

        return redirect()->to('/admin/settings/services')->with('success', 'Pengaturan layanan berhasil disimpan.');
    }

    public function serviceCardSave()
    {
        $iconList = implode(',', array_keys($this->serviceIcons));
        $rules = [
            'card_id'     => 'permit_empty|is_natural_no_zero',
            'icon'        => 'required|in_list[' . $iconList . ']',
            'card_title'  => 'required|max_length[100]',
            'description' => 'required|max_length[1000]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $section = $this->sectionModel->findSection('services');
        $sectionId = $section ? (int) $section['id'] : null;

        if (! $sectionId) {
            return redirect()->to('/admin/settings/services')->with('error', 'Bagian layanan belum tersedia.');
        }

        $data = [
            'icon'        => $this->request->getPost('icon'),
            'title'       => $this->request->getPost('card_title'),
            'description' => $this->request->getPost('description'),
        ];

        $cardId = $this->request->getPost('card_id');

        if ($cardId) {
            $existing = $this->itemModel->find((int) $cardId);
            if (! $existing || (int) $existing['section_id'] !== $sectionId) {
                return redirect()->back()->with('error', 'Kartu layanan tidak ditemukan.');
            }

            $this->itemModel->update((int) $cardId, ['content' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
            $message = 'Kartu layanan berhasil diperbarui.';
        } else {
            $position = $this->itemModel->maxPosition($sectionId) + 1;
            $this->itemModel->insert([
                'section_id' => $sectionId,
                'position'   => $position,
                'content'    => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
            $message = 'Kartu layanan berhasil ditambahkan.';
        }

        return redirect()->to('/admin/settings/services')->with('success', $message);
    }

    public function serviceCardDelete()
    {
        $cardId = $this->request->getPost('card_id');

        if (! $cardId || ! is_numeric($cardId)) {
            return redirect()->back()->with('error', 'Kartu layanan tidak valid.');
        }

        $section = $this->sectionModel->findSection('services');
        $sectionId = $section ? (int) $section['id'] : null;

        $existing = $sectionId ? $this->itemModel->find((int) $cardId) : null;
        if (! $existing || (int) $existing['section_id'] !== $sectionId) {
            return redirect()->back()->with('error', 'Kartu layanan tidak ditemukan.');
        }

        $this->itemModel->delete((int) $cardId);

        // Reindex remaining positions to keep them contiguous.
        $this->renumberPositions($sectionId);

        return redirect()->to('/admin/settings/services')->with('success', 'Kartu layanan berhasil dihapus.');
    }

    public function serviceCardReorder()
    {
        $order = $this->request->getPost('order');

        if (! is_array($order)) {
            return redirect()->back()->with('error', 'Urutan tidak valid.');
        }

        $section = $this->sectionModel->findSection('services');
        if (! $section) {
            return redirect()->to('/admin/settings/services')->with('error', 'Bagian layanan tidak ditemukan.');
        }
        $sectionId = (int) $section['id'];

        $ids = [];
        foreach ($order as $id) {
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        if (! $ids) {
            return redirect()->back()->with('error', 'Urutan tidak valid.');
        }

        // Ensure every id belongs to this section and de-duplicate.
        $existing = $this->itemModel->where('section_id', $sectionId)->findAll();
        $existingIds = array_map(fn ($row) => (int) $row['id'], $existing);
        $ids = array_values(array_unique($ids));
        foreach ($ids as $id) {
            if (! in_array($id, $existingIds, true)) {
                return redirect()->back()->with('error', 'Kartu layanan tidak ditemukan.');
            }
        }

        $position = 1;
        foreach ($ids as $id) {
            $this->itemModel->update($id, ['position' => $position]);
            $position++;
        }

        // Append any items not included in the submitted order.
        foreach ($existingIds as $id) {
            if (! in_array($id, $ids, true)) {
                $this->itemModel->update($id, ['position' => $position]);
                $position++;
            }
        }

        return redirect()->to('/admin/settings/services')->with('success', 'Urutan kartu layanan berhasil diperbarui.');
    }

    protected function renumberPositions(int $sectionId): void
    {
        $items = $this->itemModel->findBySection($sectionId);
        foreach (array_values($items) as $index => $item) {
            $this->itemModel->update($item['id'], ['position' => $index + 1]);
        }
    }

    public function portfolio()
    {
        $section = $this->sectionModel->findSection('portfolio');
        $content = $section ? json_decode($section['content'], true) : [];

        $categories = $this->portfolioCategoryModel->orderBy('position', 'ASC')->findAll();
        foreach ($categories as $i => $cat) {
            $categories[$i]['item_count'] = $this->portfolioItemCategoryModel->countByCategory((int) $cat['id']);
        }

        $items = $this->portfolioItemModel->orderBy('position', 'ASC')->findAll();
        foreach ($items as $i => $item) {
            $items[$i]['category_ids'] = $this->portfolioItemCategoryModel->categoryIdsForItem((int) $item['id']);
        }

        $editItem = null;
        $editItemId = $this->request->getGet('edit_item');
        if ($editItemId && is_numeric($editItemId)) {
            $row = $this->portfolioItemModel->find((int) $editItemId);
            if ($row) {
                $editItem = [
                    'id'           => (int) $row['id'],
                    'image'        => $row['image'],
                    'image_alt'    => $row['image_alt'] ?? '',
                    'title'        => $row['title'] ?? '',
                    'link'         => $row['link'] ?? '',
                    'category_ids' => $this->portfolioItemCategoryModel->categoryIdsForItem((int) $row['id']),
                ];
            }
        }

        $editCategory = null;
        $editCatId = $this->request->getGet('edit_cat');
        if ($editCatId && is_numeric($editCatId)) {
            $row = $this->portfolioCategoryModel->find((int) $editCatId);
            if ($row) {
                $editCategory = [
                    'id'           => (int) $row['id'],
                    'name'         => $row['name'],
                    'filter_class' => $row['filter_class'],
                ];
            }
        }

        return view('admin/settings_portfolio', [
            'title'           => 'Pengaturan Portofolio',
            'portfolio'       => $content,
            'categories'      => $categories,
            'items'           => $items,
            'media'           => $this->mediaModel->findAll(),
            'sectionEnabled'  => $section ? (bool) $section['is_enabled'] : true,
            'editItem'        => $editItem,
            'editCategory'    => $editCategory,
        ]);
    }

    public function doPortfolio()
    {
        $rules = [
            'title'    => 'required|max_length[255]',
            'subtitle' => 'permit_empty|max_length[1000]',
            'layout'   => 'required|in_list[masonry_3_columns,grid_2_columns]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $content = [
            'title'    => $this->request->getPost('title'),
            'subtitle' => $this->request->getPost('subtitle') ?? '',
            'layout'   => $this->request->getPost('layout'),
        ];
        $isEnabled = $this->request->getPost('is_enabled') ? 1 : 0;

        $section = $this->sectionModel->findSection('portfolio');
        if ($section) {
            $this->sectionModel->update($section['id'], [
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
                'layout'     => $content['layout'],
                'is_enabled' => $isEnabled,
            ]);
        } else {
            $this->sectionModel->insert([
                'section'    => 'portfolio',
                'title'      => 'Portfolio',
                'layout'     => $content['layout'],
                'is_enabled' => $isEnabled,
                'sort_order' => 3,
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
            ]);
        }

        return redirect()->to('/admin/settings/portfolio')->with('success', 'Pengaturan portofolio berhasil disimpan.');
    }

    public function portfolioCategorySave()
    {
        $rules = [
            'category_id'  => 'permit_empty|is_natural_no_zero',
            'name'         => 'required|max_length[100]',
            'filter_class' => 'required|max_length[50]|regex_match[/^[a-zA-Z][a-zA-Z0-9_-]*$/]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name        = $this->request->getPost('name');
        $filterClass = $this->request->getPost('filter_class');
        $categoryId  = $this->request->getPost('category_id');

        // Unique filter class (excluding self on edit).
        $dupe = $this->portfolioCategoryModel->where('filter_class', $filterClass)->first();
        if ($dupe && (! $categoryId || (int) $dupe['id'] !== (int) $categoryId)) {
            return redirect()->back()->withInput()->with('error', 'Kelas filter sudah digunakan oleh kategori lain.');
        }

        if ($categoryId) {
            $existing = $this->portfolioCategoryModel->find((int) $categoryId);
            if (! $existing) {
                return redirect()->back()->with('error', 'Kategori tidak ditemukan.');
            }
            $this->portfolioCategoryModel->update((int) $categoryId, [
                'name'         => $name,
                'filter_class' => $filterClass,
            ]);
            $message = 'Kategori berhasil diperbarui.';
        } else {
            $this->portfolioCategoryModel->insert([
                'name'         => $name,
                'filter_class' => $filterClass,
                'position'     => $this->portfolioCategoryModel->maxPosition() + 1,
            ]);
            $message = 'Kategori berhasil ditambahkan.';
        }

        return redirect()->to('/admin/settings/portfolio')->with('success', $message);
    }

    public function portfolioCategoryDelete()
    {
        $categoryId = $this->request->getPost('category_id');

        if (! $categoryId || ! is_numeric($categoryId)) {
            return redirect()->back()->with('error', 'Kategori tidak valid.');
        }

        $existing = $this->portfolioCategoryModel->find((int) $categoryId);
        if (! $existing) {
            return redirect()->back()->with('error', 'Kategori tidak ditemukan.');
        }

        if ($this->portfolioItemCategoryModel->countByCategory((int) $categoryId) > 0) {
            return redirect()->back()->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh item portofolio.');
        }

        $this->portfolioCategoryModel->delete((int) $categoryId);
        $this->renumberCategories();

        return redirect()->to('/admin/settings/portfolio')->with('success', 'Kategori berhasil dihapus.');
    }

    public function portfolioItemSave()
    {
        $rules = [
            'item_id'   => 'permit_empty|is_natural_no_zero',
            'title'     => 'permit_empty|max_length[255]',
            'image_alt' => 'permit_empty|max_length[255]',
            'link'      => 'permit_empty|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $itemId = $this->request->getPost('item_id');

        $imageError = $this->validateImageUpload('item_image');
        if ($imageError) {
            return redirect()->back()->withInput()->with('errors', ['item_image' => $imageError]);
        }

        $imagePath = $this->resolveImagePath('item_image', $this->request->getPost('item_image') ?: $this->request->getPost('current_item_image'));

        if (! $imagePath) {
            return redirect()->back()->withInput()->with('error', 'Gambar wajib diisi.');
        }

        $categoryIds = $this->filterCategoryIds($this->request->getPost('categories'));
        if (empty($categoryIds)) {
            return redirect()->back()->withInput()->with('error', 'Pilih minimal satu kategori.');
        }

        $link = $this->request->getPost('link');
        if ($link && ! $this->isSafeUrl($link)) {
            return redirect()->back()->withInput()->with('error', 'Link tidak aman. Gunakan URL yang valid.');
        }

        $data = [
            'image'     => $imagePath,
            'image_alt' => $this->request->getPost('image_alt') ?? '',
            'title'     => $this->request->getPost('title') ?? '',
            'link'      => $link ?? '',
        ];

        if ($itemId) {
            $existing = $this->portfolioItemModel->find((int) $itemId);
            if (! $existing) {
                return redirect()->back()->with('error', 'Item portofolio tidak ditemukan.');
            }
            $this->portfolioItemModel->update((int) $itemId, $data);
            $this->portfolioItemCategoryModel->syncForItem((int) $itemId, $categoryIds);
            $message = 'Item portofolio berhasil diperbarui.';
        } else {
            $this->portfolioItemModel->insert($data + ['position' => $this->portfolioItemModel->maxPosition() + 1]);
            $newId = (int) $this->portfolioItemModel->getInsertID();
            $this->portfolioItemCategoryModel->syncForItem($newId, $categoryIds);
            $message = 'Item portofolio berhasil ditambahkan.';
        }

        return redirect()->to('/admin/settings/portfolio')->with('success', $message);
    }

    public function portfolioItemDelete()
    {
        $itemId = $this->request->getPost('item_id');

        if (! $itemId || ! is_numeric($itemId)) {
            return redirect()->back()->with('error', 'Item portofolio tidak valid.');
        }

        $existing = $this->portfolioItemModel->find((int) $itemId);
        if (! $existing) {
            return redirect()->back()->with('error', 'Item portofolio tidak ditemukan.');
        }

        // Remove the item and its category links. The media file is intentionally not deleted.
        $this->portfolioItemCategoryModel->where('item_id', (int) $itemId)->delete();
        $this->portfolioItemModel->delete((int) $itemId);
        $this->renumberItems();

        return redirect()->to('/admin/settings/portfolio')->with('success', 'Item portofolio berhasil dihapus.');
    }

    public function portfolioItemReorder()
    {
        $order = $this->request->getPost('order');

        if (! is_array($order)) {
            return redirect()->back()->with('error', 'Urutan tidak valid.');
        }

        $ids = [];
        foreach ($order as $id) {
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        if (! $ids) {
            return redirect()->back()->with('error', 'Urutan tidak valid.');
        }

        $existing = $this->portfolioItemModel->findAll();
        $existingIds = array_map(fn ($row) => (int) $row['id'], $existing);
        $ids = array_values(array_unique($ids));
        foreach ($ids as $id) {
            if (! in_array($id, $existingIds, true)) {
                return redirect()->back()->with('error', 'Item portofolio tidak ditemukan.');
            }
        }

        $position = 1;
        foreach ($ids as $id) {
            $this->portfolioItemModel->update($id, ['position' => $position]);
            $position++;
        }
        foreach ($existingIds as $id) {
            if (! in_array($id, $ids, true)) {
                $this->portfolioItemModel->update($id, ['position' => $position]);
                $position++;
            }
        }

        return redirect()->to('/admin/settings/portfolio')->with('success', 'Urutan item portofolio berhasil diperbarui.');
    }

    protected function filterCategoryIds($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $valid = $this->portfolioCategoryModel->findAll();
        $validIds = array_map(fn ($row) => (int) $row['id'], $valid);

        $result = [];
        foreach ($raw as $id) {
            if (! is_numeric($id)) {
                continue;
            }
            $id = (int) $id;
            if (in_array($id, $validIds, true) && ! in_array($id, $result, true)) {
                $result[] = $id;
            }
        }

        return $result;
    }

    protected function renumberCategories(): void
    {
        $categories = $this->portfolioCategoryModel->orderBy('position', 'ASC')->findAll();
        foreach (array_values($categories) as $index => $cat) {
            $this->portfolioCategoryModel->update($cat['id'], ['position' => $index + 1]);
        }
    }

    protected function renumberItems(): void
    {
        $items = $this->portfolioItemModel->orderBy('position', 'ASC')->findAll();
        foreach (array_values($items) as $index => $item) {
            $this->portfolioItemModel->update($item['id'], ['position' => $index + 1]);
        }
    }

    public function testimonials()
    {
        $section = $this->sectionModel->findSection('testimonials');
        $content = $section ? json_decode($section['content'], true) : [];

        $items = [];
        if ($section) {
            foreach ($this->itemModel->findBySection((int) $section['id']) as $item) {
                $item['data'] = json_decode($item['content'], true) ?: [];
                $items[]      = $item;
            }
        }

        $edit = null;
        $editId = $this->request->getGet('edit');
        if ($section && $editId && is_numeric($editId)) {
            $editRow = $this->itemModel->find((int) $editId);
            if ($editRow && (int) $editRow['section_id'] === (int) $section['id']) {
                $data = json_decode($editRow['content'], true) ?: [];
                $edit = [
                    'id'            => (int) $editRow['id'],
                    'quote_heading' => $data['quote_heading'] ?? '',
                    'quote_body'    => $data['quote_body'] ?? '',
                    'author_name'   => $data['author_name'] ?? '',
                    'author_role'   => $data['author_role'] ?? '',
                    'avatar'        => $data['avatar'] ?? '',
                ];
            }
        }

        return view('admin/settings_testimonials', [
            'title'          => 'Pengaturan Testimoni',
            'testimonials'   => $content,
            'items'          => $items,
            'media'          => $this->mediaModel->findAll(),
            'edit'           => $edit,
            'sectionEnabled' => $section ? (bool) $section['is_enabled'] : true,
        ]);
    }

    public function doTestimonials()
    {
        $rules = [
            'title'    => 'required|max_length[255]',
            'subtitle' => 'permit_empty|max_length[1000]',
            'layout'   => 'required|in_list[carousel_2_per_slide,carousel_1_per_slide]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $content = [
            'title'    => $this->request->getPost('title'),
            'subtitle' => $this->request->getPost('subtitle') ?? '',
            'layout'   => $this->request->getPost('layout'),
        ];
        $isEnabled = $this->request->getPost('is_enabled') ? 1 : 0;

        $section = $this->sectionModel->findSection('testimonials');
        if ($section) {
            $this->sectionModel->update($section['id'], [
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
                'layout'     => $content['layout'],
                'is_enabled' => $isEnabled,
            ]);
        } else {
            $this->sectionModel->insert([
                'section'    => 'testimonials',
                'title'      => 'Testimonials',
                'layout'     => $content['layout'],
                'is_enabled' => $isEnabled,
                'sort_order' => 4,
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
            ]);
        }

        return redirect()->to('/admin/settings/testimonials')->with('success', 'Pengaturan testimoni berhasil disimpan.');
    }

    public function testimonialSave()
    {
        $rules = [
            'item_id'       => 'permit_empty|is_natural_no_zero',
            'quote_heading' => 'permit_empty|max_length[200]',
            'quote_body'    => 'required|max_length[1000]',
            'author_name'   => 'required|max_length[100]',
            'author_role'   => 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $section = $this->sectionModel->findSection('testimonials');
        $sectionId = $section ? (int) $section['id'] : null;

        if (! $sectionId) {
            return redirect()->to('/admin/settings/testimonials')->with('error', 'Bagian testimoni belum tersedia.');
        }

        $avatarError = $this->validateImageUpload('avatar');
        if ($avatarError) {
            return redirect()->back()->withInput()->with('errors', ['avatar' => $avatarError]);
        }

        $avatar = $this->resolveImagePath('avatar', $this->request->getPost('avatar') ?: $this->request->getPost('current_avatar'));

        $data = [
            'quote_heading' => $this->request->getPost('quote_heading') ?? '',
            'quote_body'    => $this->request->getPost('quote_body'),
            'author_name'   => $this->request->getPost('author_name'),
            'author_role'   => $this->request->getPost('author_role') ?? '',
            'avatar'        => $avatar,
        ];

        $itemId = $this->request->getPost('item_id');

        if ($itemId) {
            $existing = $this->itemModel->find((int) $itemId);
            if (! $existing || (int) $existing['section_id'] !== $sectionId) {
                return redirect()->back()->with('error', 'Testimoni tidak ditemukan.');
            }

            $this->itemModel->update((int) $itemId, ['content' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
            $message = 'Testimoni berhasil diperbarui.';
        } else {
            $position = $this->itemModel->maxPosition($sectionId) + 1;
            $this->itemModel->insert([
                'section_id' => $sectionId,
                'position'   => $position,
                'content'    => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
            $message = 'Testimoni berhasil ditambahkan.';
        }

        return redirect()->to('/admin/settings/testimonials')->with('success', $message);
    }

    public function testimonialDelete()
    {
        $itemId = $this->request->getPost('item_id');

        if (! $itemId || ! is_numeric($itemId)) {
            return redirect()->back()->with('error', 'Testimoni tidak valid.');
        }

        $section = $this->sectionModel->findSection('testimonials');
        $sectionId = $section ? (int) $section['id'] : null;

        $existing = $sectionId ? $this->itemModel->find((int) $itemId) : null;
        if (! $existing || (int) $existing['section_id'] !== $sectionId) {
            return redirect()->back()->with('error', 'Testimoni tidak ditemukan.');
        }

        // Remove only the testimonial row; the shared avatar/media file is preserved.
        $this->itemModel->delete((int) $itemId);
        $this->renumberPositions($sectionId);

        return redirect()->to('/admin/settings/testimonials')->with('success', 'Testimoni berhasil dihapus.');
    }

    public function testimonialReorder()
    {
        $order = $this->request->getPost('order');

        if (! is_array($order)) {
            return redirect()->back()->with('error', 'Urutan tidak valid.');
        }

        $section = $this->sectionModel->findSection('testimonials');
        if (! $section) {
            return redirect()->to('/admin/settings/testimonials')->with('error', 'Bagian testimoni tidak ditemukan.');
        }
        $sectionId = (int) $section['id'];

        $ids = [];
        foreach ($order as $id) {
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        if (! $ids) {
            return redirect()->back()->with('error', 'Urutan tidak valid.');
        }

        $existing = $this->itemModel->where('section_id', $sectionId)->findAll();
        $existingIds = array_map(fn ($row) => (int) $row['id'], $existing);
        $ids = array_values(array_unique($ids));
        foreach ($ids as $id) {
            if (! in_array($id, $existingIds, true)) {
                return redirect()->back()->with('error', 'Testimoni tidak ditemukan.');
            }
        }

        $position = 1;
        foreach ($ids as $id) {
            $this->itemModel->update($id, ['position' => $position]);
            $position++;
        }
        foreach ($existingIds as $id) {
            if (! in_array($id, $ids, true)) {
                $this->itemModel->update($id, ['position' => $position]);
                $position++;
            }
        }

        return redirect()->to('/admin/settings/testimonials')->with('success', 'Urutan testimoni berhasil diperbarui.');
    }

    public function blog()
    {
        $section = $this->sectionModel->findSection('blog');
        $content = $section ? json_decode($section['content'], true) : [];

        $articleModel = new \App\Models\ArticleModel();
        $publishedCount = $articleModel->where('status', 'published')->countAllResults();

        return view('admin/settings_blog', [
            'title'          => 'Pengaturan Blog',
            'blog'           => $content,
            'sectionEnabled' => $section ? (bool) $section['is_enabled'] : true,
            'publishedCount' => $publishedCount,
        ]);
    }

    public function doBlog()
    {
        $rules = [
            'title'       => 'required|max_length[100]',
            'subtitle'    => 'permit_empty|max_length[255]',
            'card_layout' => 'required|in_list[grid_2_columns,grid_3_columns]',
            'limit'       => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[12]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $content = [
            'title'       => $this->request->getPost('title'),
            'subtitle'    => $this->request->getPost('subtitle') ?? '',
            'card_layout' => $this->request->getPost('card_layout'),
            'limit'       => (string) $this->request->getPost('limit'),
        ];

        $isEnabled = $this->request->getPost('is_enabled') ? 1 : 0;

        $section = $this->sectionModel->findSection('blog');
        if ($section) {
            $this->sectionModel->update($section['id'], [
                'title'      => $content['title'],
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
                'layout'     => $content['card_layout'],
                'is_enabled' => $isEnabled,
            ]);
        } else {
            $this->sectionModel->insert([
                'section'    => 'blog',
                'title'      => $content['title'],
                'layout'     => $content['card_layout'],
                'is_enabled' => $isEnabled,
                'sort_order' => 5,
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
            ]);
        }

        return redirect()->to('/admin/settings/blog')->with('success', 'Pengaturan Blog berhasil disimpan.');
    }

    public function contact()
    {
        $section = $this->sectionModel->findSection('contact');
        $content = $section ? json_decode($section['content'], true) : [];

        $defaults = [
            'heading'          => 'Contact',
            'description'      => 'Quisque eget nisl id nulla sagittis auctor quis id. Aliquam quis vehicula enim, non aliquam risus.',
            'name_placeholder' => 'Your Name',
            'email_placeholder'=> 'Your Email',
            'phone_placeholder'=> 'Your Phone',
            'message_placeholder' => 'Your Message',
            'submit_label'     => 'Send Message',
        ];

        foreach ($defaults as $key => $value) {
            if (empty($content[$key])) {
                $content[$key] = $value;
            }
        }

        return view('admin/settings_contact', [
            'title'          => 'Pengaturan Contact',
            'contact'        => $content,
            'sectionEnabled' => $section ? (bool) $section['is_enabled'] : true,
        ]);
    }

    public function doContact()
    {
        $rules = [
            'heading'             => 'required|max_length[255]',
            'description'         => 'permit_empty|max_length[2000]',
            'name_placeholder'    => 'required|max_length[100]',
            'email_placeholder'   => 'required|max_length[100]',
            'phone_placeholder'   => 'required|max_length[100]',
            'message_placeholder' => 'required|max_length[100]',
            'submit_label'        => 'required|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $content = [
            'heading'             => $this->request->getPost('heading'),
            'description'         => $this->request->getPost('description') ?? '',
            'name_placeholder'    => $this->request->getPost('name_placeholder'),
            'email_placeholder'   => $this->request->getPost('email_placeholder'),
            'phone_placeholder'   => $this->request->getPost('phone_placeholder'),
            'message_placeholder' => $this->request->getPost('message_placeholder'),
            'submit_label'        => $this->request->getPost('submit_label'),
        ];

        $isEnabled = $this->request->getPost('is_enabled') ? 1 : 0;

        $section = $this->sectionModel->findSection('contact');
        if ($section) {
            $this->sectionModel->update($section['id'], [
                'title'      => $content['heading'],
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
                'layout'     => 'contact_form',
                'is_enabled' => $isEnabled,
            ]);
        } else {
            $this->sectionModel->insert([
                'section'    => 'contact',
                'title'      => $content['heading'],
                'layout'     => 'contact_form',
                'is_enabled' => $isEnabled,
                'sort_order' => 6,
                'content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
            ]);
        }

        return redirect()->to('/admin/settings/contact')->with('success', 'Pengaturan Contact berhasil disimpan.');
    }

    protected function validateImageUpload(string $field): ?string
    {
        $file = $this->request->getFile($field);

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }

        $ext = strtolower($file->getExtension());
        if (! in_array($ext, $this->imageExtensions, true)) {
            return 'File ' . $field . ' tidak diizinkan. Gunakan format JPG/PNG/WebP/ICO.';
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, $this->imageMimes, true)) {
            return 'File ' . $field . ' bukan gambar yang valid.';
        }

        return null;
    }

    protected function resolveImagePath(string $field, ?string $selectedPath): string
    {
        $file = $this->request->getFile($field);

        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, $this->imageExtensions, true)) {
                return $this->fallbackPath($field, $selectedPath);
            }

            $mime = $file->getMimeType();
            if (! in_array($mime, $this->imageMimes, true)) {
                return $this->fallbackPath($field, $selectedPath);
            }

            $uploadDir = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'portal' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'media';
            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newName = $file->getRandomName();
            if (! $file->move($uploadDir, $newName)) {
                return $this->fallbackPath($field, $selectedPath);
            }

            $relativePath = 'assets/portal/uploads/media/' . $newName;

            $this->mediaModel->insert([
                'title'     => $file->getClientName(),
                'filename'  => $relativePath,
                'mime_type' => $mime,
            ]);

            return $relativePath;
        }

        return $this->fallbackPath($field, $selectedPath);
    }

    protected function fallbackPath(string $field, ?string $selectedPath): string
    {
        if (! empty($selectedPath)) {
            return $selectedPath;
        }

        $defaults = [
            'logo'             => 'assets/portal/images/logo.png',
            'favicon'          => 'assets/portal/images/favicon.ico',
            'apple_touch_icon' => 'assets/portal/images/apple-touch-icon.png',
            'background_image' => 'assets/portal/uploads/banner-01.jpg',
        ];

        return $defaults[$field] ?? '';
    }

    protected function isSafeUrl(string $url): bool
    {
        if ($url === '#') {
            return true;
        }

        if (strpos($url, '#') === 0) {
            return true;
        }

        $parsed = parse_url($url);
        if (! isset($parsed['scheme'])) {
            return true;
        }

        return in_array(strtolower($parsed['scheme']), ['http', 'https'], true);
    }
}
