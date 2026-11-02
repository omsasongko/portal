<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Models\MediaModel;
use App\Models\HomepageSectionModel;
use App\Models\HomepageSectionItemModel;

class Settings extends BaseController
{
    protected SettingModel $settingModel;
    protected MediaModel $mediaModel;
    protected HomepageSectionModel $sectionModel;
    protected HomepageSectionItemModel $itemModel;

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
