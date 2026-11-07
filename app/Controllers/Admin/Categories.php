<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;

class Categories extends BaseController
{
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        helper('url');
        $this->categoryModel = new CategoryModel();
    }

    public function index()
    {
        $categories = $this->categoryModel->orderBy('name', 'ASC')->findAll();

        $edit   = null;
        $editId = $this->request->getGet('edit');
        if ($editId && is_numeric($editId)) {
            $row = $this->categoryModel->find((int) $editId);
            if ($row) {
                $edit = [
                    'id'          => (int) $row['id'],
                    'name'        => $row['name'],
                    'slug'        => $row['slug'],
                    'description' => $row['description'] ?? '',
                ];
            }
        }

        return view('admin/categories', [
            'title'      => 'Kategori Artikel',
            'categories' => $categories,
            'edit'       => $edit,
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
            return redirect()->back()->withInput()->with('errors', ['name' => 'Nama kategori wajib diisi.']);
        }

        // Generate the slug from the name when left blank, otherwise normalize the
        // provided slug. On edit the pre-filled slug is preserved, so renaming alone
        // never silently changes an existing slug.
        $slug = ($slug === '') ? mb_url_title($name, '-', true) : mb_url_title($slug, '-', true);

        if ($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return redirect()->back()->withInput()->with('error', 'Slug tidak valid. Gunakan huruf kecil, angka, dan tanda hubung.');
        }

        // Unique slug (excluding self on edit).
        $dupe = $this->categoryModel->where('slug', $slug)->first();
        if ($dupe && (! $id || (int) $dupe['id'] !== (int) $id)) {
            return redirect()->back()->withInput()->with('error', 'Slug sudah digunakan oleh kategori lain.');
        }

        $data = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description,
        ];

        if ($id) {
            $existing = $this->categoryModel->find((int) $id);
            if (! $existing) {
                return redirect()->back()->with('error', 'Kategori tidak ditemukan.');
            }
            $this->categoryModel->update((int) $id, $data);
            $message = 'Kategori berhasil diperbarui.';
        } else {
            $this->categoryModel->insert($data);
            $message = 'Kategori berhasil ditambahkan.';
        }

        return redirect()->to('/admin/categories')->with('success', $message);
    }

    public function delete()
    {
        $id = $this->request->getPost('id');

        if (! $id || ! is_numeric($id)) {
            return redirect()->back()->with('error', 'Kategori tidak valid.');
        }

        $existing = $this->categoryModel->find((int) $id);
        if (! $existing) {
            return redirect()->back()->with('error', 'Kategori tidak ditemukan.');
        }

        // Block deletion while still referenced by articles (once the articles table exists).
        $db = \Config\Database::connect();
        if ($db->tableExists('articles')) {
            $used = (int) $db->table('articles')->where('category_id', (int) $id)->countAllResults();
            $used += (int) ( $db->tableExists('article_categories')
                ? $db->table('article_categories')->where('category_id', (int) $id)->countAllResults()
                : 0 );
            if ($used > 0) {
                return redirect()->back()->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh artikel.');
            }
        }

        $this->categoryModel->delete((int) $id);

        return redirect()->to('/admin/categories')->with('success', 'Kategori berhasil dihapus.');
    }
}