<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title"><?= $page ? 'Edit Halaman' : 'Tambah Halaman' ?></h3>
    </div>
    <div class="card-body">
        <?php if (! empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('admin/pages/save') ?>">
            <?= csrf_field() ?>
            <?php if ($page): ?>
                <input type="hidden" name="id" value="<?= (int) $page['id'] ?>">
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="title" class="form-label">Judul Halaman <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= old('title', $page['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>" id="status" name="status">
                        <option value="draft" <?= old('status', $page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draf</option>
                        <option value="published" <?= old('status', $page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Terbit</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="slug" class="form-label">Slug</label>
                <div class="input-group">
                    <span class="input-group-text"><?= base_url('pages/') ?></span>
                    <input type="text" class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" id="slug" name="slug" value="<?= old('slug', $page['slug'] ?? '') ?>" placeholder="otomatis dari judul bila kosong">
                </div>
                <div class="form-text">Huruf kecil, angka, dan tanda hubung. Mengubah judul tidak otomatis mengubah slug yang sudah tersimpan.</div>
            </div>

            <div class="mb-3">
                <label for="content" class="form-label">Konten</label>
                <textarea class="form-control" id="content" name="content" rows="20"><?= old('content', $page['content'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= $page ? 'Simpan Perubahan' : 'Tambah Halaman' ?></button>
                <a href="<?= base_url('admin/pages') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from title (only for new pages without an existing slug)
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    const isEdit = <?= $page ? 'true' : 'false' ?>;
    let slugManuallyEdited = isEdit && slugInput.value.trim() !== '';

    slugInput.addEventListener('input', function() {
        slugManuallyEdited = true;
    });

    titleInput.addEventListener('input', function() {
        if (!slugManuallyEdited) {
            slugInput.value = titleInput.value
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        }
    });

    // Initialize TinyMCE
    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
    tinymce.init({
        selector: '#content',
        height: 400,
        menubar: 'edit insert format table',
        plugins: 'lists link image table code wordcount',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist | link image | table | code',
        valid_elements: 'p[class],br,strong,b,em,i,u,s,sub,sup,h1,h2,h3,h4,h5,h6,ul,ol,li,a[href|title|target|rel],img[src|alt|title|width|height],blockquote,pre,code,table[class],thead,tbody,tfoot,tr,th[colspan|rowspan],td[colspan|rowspan],hr,div[class],span[class]',
        convert_urls: false,
        skin: currentTheme === 'dark' ? 'oxide-dark' : 'oxide',
        content_css: currentTheme === 'dark' ? 'dark' : 'default',
        promotion: false,
        branding: false,
        license_key: 'gpl',
    });
});
</script>
<?php $this->endSection() ?>
