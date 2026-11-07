<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\Traits\SanitizesHtml;
use App\Controllers\Admin\Traits\HandlesImageUpload;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\TagModel;
use App\Models\MediaModel;
use App\Models\SettingModel;

class Articles extends BaseController
{
    use SanitizesHtml;
    use HandlesImageUpload;

    protected ArticleModel $articleModel;
    protected CategoryModel $categoryModel;
    protected TagModel $tagModel;
    protected MediaModel $mediaModel;

    public function __construct()
    {
        helper('menu');
        $this->articleModel  = new ArticleModel();
        $this->categoryModel = new CategoryModel();
        $this->tagModel      = new TagModel();
        $this->mediaModel    = new MediaModel();
    }

    /**
     * Build a JSON array that includes a fresh CSRF token so the browser
     * can continue making POST requests after token regeneration.
     */
    private function withCsrf(array $data): array
    {
        $data[csrf_token()] = csrf_hash();
        return $data;
    }

    public function index()
    {
        $articles = $this->articleModel->orderBy('updated_at', 'DESC')->findAll();

        $categories = $this->categoryModel->findAll();
        $catNames   = array_column($categories, 'name', 'id');

        return view('admin/articles', [
            'title'      => 'Artikel',
            'articles'   => $articles,
            'catNames'   => $catNames,
        ]);
    }

    public function create()
    {
        return view('admin/article_form', [
            'title'    => 'Tambah Artikel',
            'article'  => null,
            'tagIds'   => [],
            'media'    => $this->mediaModel->findAll(),
            'categories' => $this->categoryModel->orderBy('name', 'ASC')->findAll(),
            'tags'     => $this->tagModel->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function edit(int $id)
    {
        $article = $this->articleModel->find($id);
        if (! $article) {
            return redirect()->to(base_url('admin/articles'))->with('error', 'Artikel tidak ditemukan.');
        }

        return view('admin/article_form', [
            'title'      => 'Edit Artikel',
            'article'    => $article,
            'tagIds'     => $this->articleModel->tagIdsForArticle($id),
            'media'      => $this->mediaModel->findAll(),
            'categories' => $this->categoryModel->orderBy('name', 'ASC')->findAll(),
            'tags'       => $this->tagModel->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function save()
    {
        $id       = (int) $this->request->getPost('id');
        $existing = $id ? $this->articleModel->find($id) : null;

        $title   = (string) $this->request->getPost('title');
        $slug    = trim((string) ($this->request->getPost('slug') ?? ''));
        $excerpt = trim((string) ($this->request->getPost('excerpt') ?? ''));
        $body    = (string) ($this->request->getPost('body') ?? '');
        $status  = (string) $this->request->getPost('status');

        // Auto-generate slug from title when blank; changing the title later
        // never silently changes a stored slug (the edit form pre-fills it).
        $slug = ($slug === '') ? mb_url_title($title, '-', true) : mb_strtolower($slug);
        $this->request->setGlobal('post', array_merge($this->request->getPost(), ['slug' => $slug]));

        $slugRule = 'required|max_length[200]|regex_match[/^[a-z0-9]+(?:-[a-z0-9]+)*$/]';
        $slugRule .= $existing
            ? '|is_unique[articles.slug,id,' . $id . ']'
            : '|is_unique[articles.slug]';

        $rules = [
            'title'         => 'required|max_length[200]',
            'slug'          => $slugRule,
            'excerpt'       => 'permit_empty|max_length[500]',
            'body'          => 'permit_empty',
            'status'        => 'required|in_list[draft,published]',
            'cover_alt'     => 'permit_empty|max_length[255]',
            'category_id'   => 'permit_empty|is_natural_no_zero',
            'published_at'  => 'permit_empty|valid_date[Y-m-d]',
            'detail_layout' => 'required|in_list[full,sidebar]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Category must exist when provided.
        $categoryId = (int) ($this->request->getPost('category_id') ?: 0) ?: null;
        if ($categoryId !== null && ! $this->categoryModel->find($categoryId)) {
            return redirect()->back()->withInput()->with('error', 'Kategori yang dipilih tidak ditemukan.');
        }

        // Tags must exist; keep only valid IDs.
        $postedTags = $this->request->getPost('tags') ?? [];
        $tagIds     = [];
        if (is_array($postedTags)) {
            foreach ($postedTags as $tagId) {
                $tagId = (int) $tagId;
                if ($tagId > 0 && $this->tagModel->find($tagId)) {
                    $tagIds[] = $tagId;
                }
            }
        }

        // Cover image: validate upload, then resolve (upload wins over library selection).
        $imageError = $this->validateImageUpload('cover_image');
        if ($imageError) {
            return redirect()->back()->withInput()->with('errors', ['cover_image' => $imageError]);
        }
        $coverImage = $this->resolveImagePath(
            'cover_image',
            $this->request->getPost('cover_image') ?: $this->request->getPost('current_cover_image')
        );

        // Publication date: default to today when publishing without a date.
        $publishedAt = $this->request->getPost('published_at') ?: null;
        if ($status === 'published' && empty($publishedAt)) {
            $publishedAt = date('Y-m-d');
        }

        $data = [
            'title'         => $title,
            'slug'          => $slug,
            'excerpt'       => $excerpt !== '' ? $excerpt : null,
            'body'          => $this->sanitizeHtml($body),
            'cover_image'   => $coverImage !== '' ? $coverImage : null,
            'cover_alt'     => $this->request->getPost('cover_alt') ?: null,
            'category_id'   => $categoryId,
            'detail_layout' => $this->request->getPost('detail_layout'),
            'status'        => $status,
            'published_at'  => $publishedAt,
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if ($existing) {
                $this->articleModel->update($id, $data);
                $articleId = $id;
                $message   = 'Artikel berhasil diperbarui.';
            } else {
                $articleId = $this->articleModel->insert($data);
                $message   = 'Artikel berhasil ditambahkan.';
            }

            $this->articleModel->syncTags((int) $articleId, $tagIds);

            $db->transComplete();
        } catch (Throwable $e) {
            $db->transRollback();
            log_message('error', 'Article save failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan artikel. Silakan coba lagi.');
        }

        return redirect()->to(base_url('admin/articles'))->with('success', $message);
    }

    public function delete()
    {
        $id      = (int) $this->request->getPost('id');
        $article = $this->articleModel->find($id);

        if (! $article) {
            return redirect()->to(base_url('admin/articles'))->with('error', 'Artikel tidak ditemukan.');
        }

        // Remove tag links + the article row only. Shared media (cover image)
        // is never deleted — it belongs to the media library.
        \Config\Database::connect()->table('article_tags')->where('article_id', $id)->delete();
        $this->articleModel->delete($id);

        return redirect()->to(base_url('admin/articles'))->with('success', 'Artikel berhasil dihapus.');
    }

    /**
     * Authenticated preview — renders the public detail view for any status
     * (draft or published) with a preview banner.
     */
    public function preview(int $id)
    {
        $article = $this->articleModel->find($id);
        if (! $article) {
            return redirect()->to(base_url('admin/articles'))->with('error', 'Artikel tidak ditemukan.');
        }

        $settingModel = new SettingModel();
        $identity     = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');

        $category = $article['category_id'] ? $this->categoryModel->find((int) $article['category_id']) : null;
        $tagIds   = $this->articleModel->tagIdsForArticle($id);
        $tags     = $tagIds ? $this->tagModel->whereIn('id', $tagIds)->findAll() : [];

        $related = ($article['detail_layout'] === 'sidebar')
            ? $this->articleModel->relatedPublished($id, $article['category_id'] ? (int) $article['category_id'] : null)
            : [];

        $this->response->setHeader('Cache-Control', 'no-store');

        return view('portal/blog_detail', [
            'article'     => $article,
            'category'    => $category,
            'tags'        => $tags,
            'related'     => $related,
            'identity'    => $identity,
            'menuItems'   => build_public_menu(true),
            'isPreview'   => true,
        ]);
    }

    /**
     * Return the CURRENT CSRF hash so client-side forms can stay fresh after
     * a POST elsewhere rotated the token (CSRF regenerate = true). GET does
     * not rotate the token, so this only exposes the current value — the same
     * value already visible in this page's own markup, no security leak.
     */
    public function csrfRefresh()
    {
        return $this->response->setJSON($this->withCsrf(['ok' => true]));
    }

    /**
     * Preview the article form's CURRENT unsaved content.
     *
     * The form is POSTed here (new tab) with tinymce content synced into the
     * body textarea first. Nothing is written to the database: the posted
     * fields are sanitized with the same rules as save() and rendered through
     * the public detail view marked as an unsaved-admin preview. The payload
     * lives only in the request, so previews are isolated per tab/article and
     * can never go stale.
     */
    public function previewUnsaved()
    {
        $post = $this->request->getPost();

        $title  = trim((string) ($post['title'] ?? ''));
        $slug   = trim((string) ($post['slug'] ?? ''));
        $slug   = ($slug === '') ? mb_url_title($title !== '' ? $title : 'artikel-tanpa-judul', '-', true) : mb_strtolower($slug);
        $body   = (string) ($post['body'] ?? '');

        $status  = in_array(($post['status'] ?? ''), ['draft', 'published'], true) ? $post['status'] : 'draft';
        $layout  = (($post['detail_layout'] ?? '') === 'full') ? 'full' : 'sidebar';
        $catId   = (int) ($post['category_id'] ?? 0);
        if ($catId > 0 && ! $this->categoryModel->find($catId)) {
            $catId = 0;
        }

        // Cover: library selection wins for preview; a freshly uploaded but
        // unsaved file lives only on the client and cannot be rendered here.
        $cover = '';
        foreach (['cover_image', 'current_cover_image'] as $key) {
            $candidate = trim((string) ($post[$key] ?? ''));
            if ($candidate !== '') {
                $cover = $candidate;
                break;
            }
        }

        $publishedAt = trim((string) ($post['published_at'] ?? ''));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $publishedAt)) {
            $publishedAt = date('Y-m-d');
        }

        // Same sanitization as save(): allowlist tags/attributes, strip
        // scripts/handlers/unsafe URLs — identical rendering rules.
        $article = [
            'id'            => (int) ($post['id'] ?? 0),
            'title'         => $title !== '' ? $title : 'Artikel tanpa judul',
            'slug'          => $slug,
            'excerpt'       => mb_substr(trim((string) ($post['excerpt'] ?? '')), 0, 500),
            'body'          => $this->sanitizeHtml($body),
            'cover_image'   => $cover !== '' ? $cover : null,
            'cover_alt'     => trim((string) ($post['cover_alt'] ?? '')),
            'category_id'   => $catId ?: null,
            'detail_layout' => $layout,
            'status'        => $status,
            'published_at'  => $publishedAt,
        ];

        $settingModel = new SettingModel();
        $identity     = $settingModel->getGroup('site_identity') + $settingModel->getGroup('global_appearance');

        $category = $catId ? $this->categoryModel->find($catId) : null;

        $postedTags = $post['tags'] ?? [];
        $tagIds     = [];
        if (is_array($postedTags)) {
            foreach ($postedTags as $tagId) {
                $tagId = (int) $tagId;
                if ($tagId > 0 && $this->tagModel->find($tagId)) {
                    $tagIds[] = $tagId;
                }
            }
        }
        $tags = $tagIds ? $this->tagModel->whereIn('id', $tagIds)->findAll() : [];

        $related = ($layout === 'sidebar')
            ? $this->articleModel->relatedPublished($article['id'], $catId ?: null)
            : [];

        $this->response->setHeader('Cache-Control', 'no-store');

        return view('portal/blog_detail', [
            'article'     => $article,
            'category'    => $category,
            'tags'        => $tags,
            'related'     => $related,
            'identity'    => $identity,
            'menuItems'   => build_public_menu(true),
            'isPreview'   => true,
            'isUnsaved'   => true,
        ]);
    }

    /**
     * Inline image upload for TinyMCE (images_upload_handler).
     */
    public function uploadImage()
    {
        $file = $this->request->getFile('file');

        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'File upload gagal.']));
        }

        $ext = strtolower($file->getExtension());
        $clientExt = strtolower($file->getClientExtension());
        if (! in_array($ext, $this->imageExtensions, true) || ! in_array($clientExt, $this->imageExtensions, true)) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.']));
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, $this->imageMimes, true)) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'File bukan gambar yang valid.']));
        }

        $size = $file->getSize();
        if ($size < $this->imageMinSize) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Ukuran file terlalu kecil. Minimum 50 KB.']));
        }

        if ($size > $this->imageMaxSize) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Ukuran file terlalu besar. Maksimum 10 MB.']));
        }

        $imageInfo = @getimagesize($file->getTempName());
        if ($imageInfo === false) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'File bukan gambar yang valid (konten tidak dikenali).']));
        }

        $uploadDir = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'portal' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'media';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $file->getRandomName();
        if (! $file->move($uploadDir, $newName)) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Gagal menyimpan file.']));
        }

        $relativePath = 'assets/portal/uploads/media/' . $newName;

        $this->mediaModel->insert([
            'title'     => $file->getClientName(),
            'filename'  => $relativePath,
            'mime_type' => $mime,
        ]);

        return $this->response->setJSON($this->withCsrf([
            'location' => base_url($relativePath),
        ]));
    }

    /**
     * Crop an existing media image using GD.
     */
    public function cropImage()
    {
        $json = $this->request->getJSON(true);

        if (empty($json)) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Data tidak valid.']));
        }

        $src    = $json['src'] ?? '';
        $x      = $json['x'] ?? null;
        $y      = $json['y'] ?? null;
        $width  = $json['width'] ?? null;
        $height = $json['height'] ?? null;

        if ($src === '' || $x === null || $y === null || $width === null || $height === null) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Parameter tidak lengkap.']));
        }

        $x      = (int) $x;
        $y      = (int) $y;
        $width  = (int) $width;
        $height = (int) $height;

        if ($x < 0 || $y < 0 || $width <= 0 || $height <= 0) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Koordinat pemotongan tidak valid.']));
        }

        // Resolve absolute path, stripping any leading slash
        $src = ltrim($src, '/');
        $absPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $src);

        if (! is_file($absPath)) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'File sumber tidak ditemukan.']));
        }

        $imageInfo = @getimagesize($absPath);
        if ($imageInfo === false) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'File sumber bukan gambar yang valid.']));
        }

        $srcWidth  = $imageInfo[0];
        $srcHeight = $imageInfo[1];
        $srcType   = $imageInfo[2];

        if (($x + $width) > $srcWidth || ($y + $height) > $srcHeight) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Area pemotongan melebihi dimensi gambar.']));
        }

        // Check for animated GIF
        if ($srcType === IMAGETYPE_GIF) {
            if ($this->isAnimatedGif($absPath)) {
                return $this->response->setStatusCode(400)
                    ->setJSON($this->withCsrf(['error' => 'Pemotongan tidak tersedia untuk GIF animasi. Gunakan gambar statis.']));
            }
        }

        // Load source image
        $srcImage = match ($srcType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($absPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($absPath),
            IMAGETYPE_GIF  => @imagecreatefromgif($absPath),
            default        => false,
        };

        if (! $srcImage) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Gagal memproses gambar sumber.']));
        }

        // Create cropped image
        $cropped = imagecreatetruecolor($width, $height);

        // Preserve transparency for PNG and WebP
        if (in_array($srcType, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            imagealphablending($cropped, false);
            imagesavealpha($cropped, true);
            $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
            imagefilledrectangle($cropped, 0, 0, $width, $height, $transparent);
        }

        // Preserve transparency for GIF
        if ($srcType === IMAGETYPE_GIF) {
            $transIndex = imagecolortransparent($srcImage);
            if ($transIndex >= 0) {
                $transColor = imagecolorsforindex($srcImage, $transIndex);
                $newTransIndex = imagecolorallocate($cropped, $transColor['red'], $transColor['green'], $transColor['blue']);
                imagefill($cropped, 0, 0, $newTransIndex);
                imagecolortransparent($cropped, $newTransIndex);
            }
        }

        imagecopy($cropped, $srcImage, 0, 0, $x, $y, $width, $height);
        imagedestroy($srcImage);

        // Determine output extension and save
        $extMap = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF  => 'gif',
        ];
        $mimeMap = [
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG  => 'image/png',
            IMAGETYPE_WEBP => 'image/webp',
            IMAGETYPE_GIF  => 'image/gif',
        ];

        $ext  = $extMap[$srcType] ?? 'jpg';
        $mime = $mimeMap[$srcType] ?? 'image/jpeg';

        $uploadDir = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'portal' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'media';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName  = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newName;

        $saved = match ($srcType) {
            IMAGETYPE_JPEG => imagejpeg($cropped, $destPath, 90),
            IMAGETYPE_PNG  => imagepng($cropped, $destPath, 6),
            IMAGETYPE_WEBP => imagewebp($cropped, $destPath, 90),
            IMAGETYPE_GIF  => imagegif($cropped, $destPath),
            default        => false,
        };

        imagedestroy($cropped);

        if (! $saved) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Gagal menyimpan hasil pemotongan.']));
        }

        // Validate output size
        $outputSize = filesize($destPath);
        if ($outputSize > $this->imageMaxSize) {
            @unlink($destPath);
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'Ukuran file hasil pemotongan terlalu besar. Maksimum 10 MB.']));
        }

        $relativePath = 'assets/portal/uploads/media/' . $newName;

        $this->mediaModel->insert([
            'title'     => $newName,
            'filename'  => $relativePath,
            'mime_type' => $mime,
        ]);

        return $this->response->setJSON($this->withCsrf([
            'location' => base_url($relativePath),
        ]));
    }

    /**
     * Media library listing (images only, newest first).
     */
    public function mediaLibrary()
    {
        $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        $media = $this->mediaModel
            ->whereIn('mime_type', $imageMimes)
            ->orderBy('id', 'DESC')
            ->findAll();

        $items = [];
        foreach ($media as $row) {
            $items[] = [
                'url'   => base_url($row['filename']),
                'title' => $row['title'],
                'alt'   => '',
            ];
        }

        return $this->response->setJSON($this->withCsrf(['items' => $items]));
    }

    /**
     * Parse a YouTube URL and return an embeddable HTML snippet.
     */
    public function embedYoutube()
    {
        $json = $this->request->getJSON(true);

        if (empty($json) || empty($json['url'])) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'URL YouTube tidak valid.']));
        }

        $url = trim($json['url']);
        $videoId = $this->extractYoutubeId($url);

        if ($videoId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON($this->withCsrf(['error' => 'URL YouTube tidak valid. Gunakan link dari youtube.com atau youtu.be.']));
        }

        $embedUrl = 'https://www.youtube-nocookie.com/embed/' . $videoId;

        $html = '<div class="video-embed-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%;">'
            . '<iframe src="' . htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8') . '"'
            . ' width="560" height="315"'
            . ' frameborder="0"'
            . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"'
            . ' allowfullscreen'
            . ' title="YouTube video"'
            . ' style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"'
            . '></iframe>'
            . '</div>';

        return $this->response->setJSON($this->withCsrf([
            'html'    => $html,
            'videoId' => $videoId,
        ]));
    }

    /**
     * Extract YouTube video ID from various URL formats.
     */
    private function extractYoutubeId(string $url): ?string
    {
        // youtube.com/watch?v=ID or youtube.com/shorts/ID
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?.*v=([A-Za-z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }

        if (preg_match('/(?:https?:\/\/)?(?:www\.)?youtube\.com\/shorts\/([A-Za-z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }

        // youtu.be/ID
        if (preg_match('/(?:https?:\/\/)?youtu\.be\/([A-Za-z0-9_-]{11})/', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Check whether a GIF file contains multiple frames (is animated).
     *
     * Scans the raw binary for image descriptor markers (0x2C) that appear
     * after the header/global-color-table. More than one means animation.
     */
    private function isAnimatedGif(string $filepath): bool
    {
        $contents = @file_get_contents($filepath);
        if ($contents === false || strlen($contents) < 14) {
            return false;
        }

        // Verify GIF signature
        $sig = substr($contents, 0, 6);
        if ($sig !== 'GIF87a' && $sig !== 'GIF89a') {
            return false;
        }

        // Skip past the header (6) + logical screen descriptor (7) + global color table
        $packed = ord($contents[10]);
        $gctFlag = ($packed >> 7) & 1;
        $gctSize = $gctFlag ? 3 * (2 << ($packed & 0x07)) : 0;
        $offset = 13 + $gctSize;

        $frames = 0;
        $len = strlen($contents);

        while ($offset < $len) {
            $byte = ord($contents[$offset]);

            if ($byte === 0x3B) {
                break; // trailer
            }

            if ($byte === 0x2C) {
                // Image descriptor found
                $frames++;
                if ($frames > 1) {
                    return true;
                }
                // Skip image descriptor header (9 bytes: marker + 4 position + 4 size/packed)
                $offset += 9;
                if ($offset >= $len) {
                    break;
                }
                $imgPacked = ord($contents[$offset]);
                $offset++;
                // Skip local color table if present
                if ($imgPacked & 0x80) {
                    $offset += 3 * (2 << ($imgPacked & 0x07));
                }
                // Skip LZW minimum code size
                $offset++;
                // Skip sub-blocks
                while ($offset < $len) {
                    $blockSize = ord($contents[$offset]);
                    $offset++;
                    if ($blockSize === 0) {
                        break;
                    }
                    $offset += $blockSize;
                }
            } elseif ($byte === 0x21) {
                // Extension block
                $offset += 2; // skip marker + label
                // Skip sub-blocks
                while ($offset < $len) {
                    $blockSize = ord($contents[$offset]);
                    $offset++;
                    if ($blockSize === 0) {
                        break;
                    }
                    $offset += $blockSize;
                }
            } else {
                // Unknown byte — skip to avoid infinite loop
                $offset++;
            }
        }

        return false;
    }
}
