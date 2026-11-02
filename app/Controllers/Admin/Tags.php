<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TagModel;

class Tags extends BaseController
{
    protected TagModel $tagModel;

    public function __construct()
    {
        helper('url');
        $this->tagModel = new TagModel();
    }

    public function index()
    {
        $tags = $this->tagModel->orderBy('name', 'ASC')->findAll();

        $edit   = null;
        $editId = $this->request->getGet('edit');
        if ($editId && is_numeric($editId)) {
            $row = $this->tagModel->find((int) $editId);
            if ($row) {
                $edit = [
                    'id'          => (int) $row['id'],
                    'name'        => $row['name'],
                    'slug'        => $row['slug'],
                    'description' => $row['description'] ?? '',
                ];
            }
        }

        return view('admin/tags', [
            'title' => 'Tag Artikel',
            'tags'  => $tags,
            'edit'  => $edit,
        ]);
    }

    public function save()
    {
        $rules = [
            'id'          => 'permit_empty|is_natural_no_zero',
            'name'        => 'required|max_length[100]',
            'slug'        => 'permit_empty|max_length[150]',
            'description' => 'permit_empty|max_length[1000]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $id          = $this->request->getPost('id');
        $name        = trim((string) $this->request->getPost('name'));
        $description = trim((string) ($this->request->getPost('description') ?? ''));
        $slug        = trim((string) ($this->request->getPost('slug') ?? ''));

        if ($name === '') {
            return redirect()->back()->withInput()->with('errors', ['name' => 'Nama tag wajib diisi.']);
        }

        // Generate the slug from the name when left blank, otherwise normalize the
        // provided slug. On edit the pre-filled slug is preserved, so renaming alone
        // never silently changes an existing slug.
        $slug = ($slug === '') ? mb_url_title($name, '-', true) : mb_url_title($slug, '-', true);

        if ($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return redirect()->back()->withInput()->with('error', 'Slug tidak valid. Gunakan huruf kecil, angka, dan tanda hubung.');
        }

        // Unique slug (excluding self on edit).
        $dupe = $this->tagModel->where('slug', $slug)->first();
        if ($dupe && (! $id || (int) $dupe['id'] !== (int) $id)) {
            return redirect()->back()->withInput()->with('error', 'Slug sudah digunakan oleh tag lain.');
        }

        $data = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description,
        ];

        if ($id) {
            $existing = $this->tagModel->find((int) $id);
            if (! $existing) {
                return redirect()->back()->with('error', 'Tag tidak ditemukan.');
            }
            $this->tagModel->update((int) $id, $data);
            $message = 'Tag berhasil diperbarui.';
        } else {
            $this->tagModel->insert($data);
            $message = 'Tag berhasil ditambahkan.';
        }

        return redirect()->to('/admin/tags')->with('success', $message);
    }

    public function delete()
    {
        $id = $this->request->getPost('id');

        if (! $id || ! is_numeric($id)) {
            return redirect()->back()->with('error', 'Tag tidak valid.');
        }

        $existing = $this->tagModel->find((int) $id);
        if (! $existing) {
            return redirect()->back()->with('error', 'Tag tidak ditemukan.');
        }

        // Block deletion while still referenced by articles (once the article_tags table exists).
        $db = \Config\Database::connect();
        if ($db->tableExists('article_tags')) {
            $used = (int) $db->table('article_tags')->where('tag_id', (int) $id)->countAllResults();
            if ($used > 0) {
                return redirect()->back()->with('error', 'Tag tidak dapat dihapus karena masih digunakan oleh artikel.');
            }
        }

        $this->tagModel->delete((int) $id);

        return redirect()->to('/admin/tags')->with('success', 'Tag berhasil dihapus.');
    }
}