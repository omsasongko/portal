<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\Traits\SanitizesHtml;
use App\Models\PageModel;
use App\Models\SettingModel;

class Pages extends BaseController
{
    use SanitizesHtml;

    protected PageModel $pageModel;

    public function __construct()
    {
        $this->pageModel = new PageModel();
    }

    private function loadIdentity(): array
    {
        $settingModel = new SettingModel();
        return $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');
    }

    public function index()
    {
        $pages = $this->pageModel->orderBy('updated_at', 'DESC')->findAll();
        $edit  = null;

        $editId = $this->request->getGet('edit');
        if ($editId) {
            $edit = $this->pageModel->find((int) $editId);
        }

        return view('admin/pages', [
            'title'    => 'Halaman',
            'pages'    => $pages,
            'edit'     => $edit,
            'identity' => $this->loadIdentity(),
        ]);
    }

    public function create()
    {
        return view('admin/page_form', [
            'title'    => 'Tambah Halaman',
            'page'     => null,
            'identity' => $this->loadIdentity(),
        ]);
    }

    public function edit(int $id)
    {
        $page = $this->pageModel->find($id);
        if (! $page) {
            return redirect()->to(base_url('admin/pages'))->with('error', 'Halaman tidak ditemukan.');
        }

        return view('admin/page_form', [
            'title'    => 'Edit Halaman',
            'page'     => $page,
            'identity' => $this->loadIdentity(),
        ]);
    }

    public function save()
    {
        $id = (int) $this->request->getPost('id');
        $existing = $id ? $this->pageModel->find($id) : null;

        $title   = $this->request->getPost('title');
        $slug    = trim($this->request->getPost('slug') ?? '');
        $content = $this->request->getPost('content') ?? '';
        $status  = $this->request->getPost('status');

        // Auto-generate slug from title if empty
        if (empty($slug)) {
            $slug = mb_url_title($title, '-', true);
        } else {
            $slug = mb_strtolower($slug);
        }

        // Replace post data slug with processed slug for validation
        $this->request->setGlobal('post', array_merge($this->request->getPost(), ['slug' => $slug]));

        $slugRule = 'required|max_length[200]|regex_match[/^[a-z0-9]+(?:-[a-z0-9]+)*$/]';
        if ($existing) {
            $slugRule .= '|is_unique[pages.slug,id,' . $id . ']';
        } else {
            $slugRule .= '|is_unique[pages.slug]';
        }

        $rules = [
            'title'   => 'required|max_length[200]',
            'slug'    => $slugRule,
            'content' => 'permit_empty',
            'status'  => 'required|in_list[draft,published]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Sanitize content with allowlist
        $content = $this->sanitizeHtml($content);

        $data = [
            'title'   => $title,
            'slug'    => $slug,
            'content' => $content,
            'status'  => $status,
        ];

        if ($existing) {
            $this->pageModel->update($id, $data);
            $message = 'Halaman berhasil diperbarui.';
        } else {
            $this->pageModel->insert($data);
            $message = 'Halaman berhasil ditambahkan.';
        }

        return redirect()->to(base_url('admin/pages'))->with('success', $message);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        $page = $this->pageModel->find($id);

        if (! $page) {
            return redirect()->to(base_url('admin/pages'))->with('error', 'Halaman tidak ditemukan.');
        }

        $this->pageModel->delete($id);

        return redirect()->to(base_url('admin/pages'))->with('success', 'Halaman berhasil dihapus.');
    }
}
