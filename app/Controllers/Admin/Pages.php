<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PageModel;

class Pages extends BaseController
{
    protected PageModel $pageModel;

    protected array $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'a', 'img',
        'blockquote', 'pre', 'code',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'hr', 'div', 'span',
    ];

    protected array $allowedAttributes = [
        'a'     => ['href', 'title', 'target', 'rel'],
        'img'   => ['src', 'alt', 'title', 'width', 'height'],
        'td'    => ['colspan', 'rowspan'],
        'th'    => ['colspan', 'rowspan'],
        'div'   => ['class'],
        'span'  => ['class'],
        'p'     => ['class'],
        'table' => ['class'],
    ];

    public function __construct()
    {
        $this->pageModel = new PageModel();
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
            'title' => 'Halaman',
            'pages' => $pages,
            'edit'  => $edit,
        ]);
    }

    public function create()
    {
        return view('admin/page_form', [
            'title' => 'Tambah Halaman',
            'page'  => null,
        ]);
    }

    public function edit(int $id)
    {
        $page = $this->pageModel->find($id);
        if (! $page) {
            return redirect()->to(base_url('admin/pages'))->with('error', 'Halaman tidak ditemukan.');
        }

        return view('admin/page_form', [
            'title' => 'Edit Halaman',
            'page'  => $page,
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

    protected function sanitizeHtml(string $html): string
    {
        if (empty(trim($html))) {
            return '';
        }

        $allowedTagStr = implode('', array_map(fn($t) => "<{$t}>", $this->allowedTags));
        $html = strip_tags($html, $allowedTagStr);

        // Parse and filter attributes
        $html = preg_replace_callback(
            '/<(\w+)((?:\s+[^>]*)?)>/s',
            function ($matches) {
                $tag = strtolower($matches[1]);
                $attrStr = $matches[2];

                if (! in_array($tag, $this->allowedTags, true)) {
                    return '';
                }

                if (empty(trim($attrStr))) {
                    return "<{$tag}>";
                }

                $allowedForTag = $this->allowedAttributes[$tag] ?? [];
                if (empty($allowedForTag)) {
                    return "<{$tag}>";
                }

                $filteredAttrs = '';
                preg_match_all('/(\w[\w-]*)=(?:"([^"]*)"|\'([^\']*)\'|(\S+))/', $attrStr, $attrMatches, PREG_SET_ORDER);
                foreach ($attrMatches as $attr) {
                    $name  = strtolower($attr[1]);
                    $value = $attr[2] !== '' ? $attr[2] : ($attr[3] !== '' ? $attr[3] : ($attr[4] ?? ''));

                    if (! in_array($name, $allowedForTag, true)) {
                        continue;
                    }

                    // Block javascript: URLs in href and src
                    if (in_array($name, ['href', 'src'], true)) {
                        $testVal = strtolower(trim(preg_replace('/[\x00-\x20]+/', '', $value)));
                        if (preg_match('/^(?:javascript|vbscript|data):/i', $testVal)) {
                            continue;
                        }
                    }

                    $filteredAttrs .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                }

                return "<{$tag}{$filteredAttrs}>";
            },
            $html
        );

        return $html;
    }
}
