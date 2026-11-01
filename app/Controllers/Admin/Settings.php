<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;
use App\Models\MediaModel;
use App\Models\HomepageSectionModel;

class Settings extends BaseController
{
    protected SettingModel $settingModel;
    protected MediaModel $mediaModel;
    protected HomepageSectionModel $sectionModel;

    protected array $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'ico'];
    protected array $imageMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/x-icon', 'image/vnd.microsoft.icon'];

    public function __construct()
    {
        $this->settingModel = new SettingModel();
        $this->mediaModel   = new MediaModel();
        $this->sectionModel = new HomepageSectionModel();
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
