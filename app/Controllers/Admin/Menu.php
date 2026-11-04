<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MenuItemModel;
use App\Models\PageModel;
use App\Models\HomepageSectionModel;

class Menu extends BaseController
{
    protected MenuItemModel $menuModel;
    protected PageModel $pageModel;
    protected HomepageSectionModel $sectionModel;

    protected array $knownSections = ['home', 'about', 'services', 'portfolio', 'testimonials', 'blog', 'contact'];

    public function __construct()
    {
        $this->menuModel    = new MenuItemModel();
        $this->pageModel    = new PageModel();
        $this->sectionModel = new HomepageSectionModel();
    }

    public function index()
    {
        $items = $this->menuModel->getOrdered();
        $edit  = null;

        $editId = $this->request->getGet('edit');
        if ($editId) {
            $edit = $this->menuModel->find((int) $editId);
        }

        return view('admin/menu', [
            'title'    => 'Menu Navigasi',
            'items'    => $items,
            'edit'     => $edit,
            'sections' => $this->knownSections,
            'pages'    => $this->pageModel->orderBy('title', 'ASC')->findAll(),
        ]);
    }

    public function save()
    {
        $id = (int) $this->request->getPost('id');
        $existing = $id ? $this->menuModel->find($id) : null;

        $type = $this->request->getPost('type');

        $rules = [
            'label' => 'required|max_length[100]',
            'type'  => 'required|in_list[section,page,url,articles]',
        ];

        if ($type === 'section') {
            $rules['target_section'] = 'required|in_list[' . implode(',', $this->knownSections) . ']';
        } elseif ($type === 'page') {
            $rules['target_page'] = 'required';
        } elseif ($type === 'url') {
            $rules['target_url'] = 'required|max_length[500]|valid_url_strict[https,http]';
        }
        // type 'articles' needs no target input — it always points to the /blog listing.

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $label = $this->request->getPost('label');

        switch ($type) {
            case 'section':
                $target = $this->request->getPost('target_section');
                break;
            case 'page':
                $target = $this->request->getPost('target_page');
                break;
            case 'url':
                $target = $this->request->getPost('target_url');
                break;
            case 'articles':
                $target = 'blog';
                break;
            default:
                $target = '';
        }

        // Validate page target exists
        if ($type === 'page') {
            $page = $this->pageModel->findBySlug($target);
            if (! $page) {
                return redirect()->back()->withInput()->with('error', 'Halaman yang dipilih tidak ditemukan.');
            }
        }

        $data = [
            'label'  => $label,
            'type'   => $type,
            'target' => $target,
        ];

        if ($existing) {
            $data['is_visible'] = $this->request->getPost('is_visible') ? 1 : 0;
            $this->menuModel->update($id, $data);
            $message = 'Menu berhasil diperbarui.';
        } else {
            $data['sort_order'] = $this->menuModel->getNextSortOrder();
            $data['is_visible'] = 1;
            $this->menuModel->insert($data);
            $message = 'Menu berhasil ditambahkan.';
        }

        return redirect()->to(base_url('admin/menu'))->with('success', $message);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        $item = $this->menuModel->find($id);

        if (! $item) {
            return redirect()->to(base_url('admin/menu'))->with('error', 'Item menu tidak ditemukan.');
        }

        $this->menuModel->delete($id);

        return redirect()->to(base_url('admin/menu'))->with('success', 'Item menu berhasil dihapus.');
    }

    public function moveUp()
    {
        $id = (int) $this->request->getPost('id');
        $current = $this->menuModel->find($id);

        if (! $current) {
            return redirect()->to(base_url('admin/menu'))->with('error', 'Item menu tidak ditemukan.');
        }

        $above = $this->menuModel
            ->where('sort_order <', $current['sort_order'])
            ->orderBy('sort_order', 'DESC')
            ->first();

        if ($above) {
            $this->menuModel->update($current['id'], ['sort_order' => $above['sort_order']]);
            $this->menuModel->update($above['id'], ['sort_order' => $current['sort_order']]);
        }

        return redirect()->to(base_url('admin/menu'));
    }

    public function moveDown()
    {
        $id = (int) $this->request->getPost('id');
        $current = $this->menuModel->find($id);

        if (! $current) {
            return redirect()->to(base_url('admin/menu'))->with('error', 'Item menu tidak ditemukan.');
        }

        $below = $this->menuModel
            ->where('sort_order >', $current['sort_order'])
            ->orderBy('sort_order', 'ASC')
            ->first();

        if ($below) {
            $this->menuModel->update($current['id'], ['sort_order' => $below['sort_order']]);
            $this->menuModel->update($below['id'], ['sort_order' => $current['sort_order']]);
        }

        return redirect()->to(base_url('admin/menu'));
    }

    public function toggleVisibility()
    {
        $id = (int) $this->request->getPost('id');
        $item = $this->menuModel->find($id);

        if (! $item) {
            return redirect()->to(base_url('admin/menu'))->with('error', 'Item menu tidak ditemukan.');
        }

        $this->menuModel->update($id, ['is_visible' => $item['is_visible'] ? 0 : 1]);

        return redirect()->to(base_url('admin/menu'))->with('success', 'Visibilitas menu berhasil diubah.');
    }
}
