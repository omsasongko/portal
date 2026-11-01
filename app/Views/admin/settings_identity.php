<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>
<h1 class="h3 mb-4">Pengaturan Identitas Situs</h1>

<div class="card admin-card mb-4">
    <div class="card-body">
        <?php if (session()->has('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session()->getFlashdata('errors') as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('admin/settings/identity') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <h5 class="mb-3">Identitas Utama</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="site_title" class="form-label">Judul Situs</label>
                    <input type="text" class="form-control <?= isset($errors['site_title']) ? 'is-invalid' : '' ?>" id="site_title" name="site_title" value="<?= old('site_title', $settings['site_title'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="site_name" class="form-label">Nama Situs</label>
                    <input type="text" class="form-control <?= isset($errors['site_name']) ? 'is-invalid' : '' ?>" id="site_name" name="site_name" value="<?= old('site_name', $settings['site_name'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="logo" class="form-label">Logo</label>
                <?= view('admin/_media_selector', ['name' => 'logo', 'current' => $settings['logo'] ?? '', 'media' => $media, 'accept' => 'image/*']) ?>
            </div>

            <h5 class="mb-3 mt-4">SEO / Meta</h5>
            <div class="mb-3">
                <label for="meta_keywords" class="form-label">Meta Keywords</label>
                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?= old('meta_keywords', $settings['meta_keywords'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="meta_description" class="form-label">Meta Deskripsi</label>
                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?= old('meta_description', $settings['meta_description'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label for="meta_author" class="form-label">Meta Author</label>
                <input type="text" class="form-control" id="meta_author" name="meta_author" value="<?= old('meta_author', $settings['meta_author'] ?? '') ?>">
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="favicon" class="form-label">Favicon</label>
                    <?= view('admin/_media_selector', ['name' => 'favicon', 'current' => $settings['favicon'] ?? '', 'media' => $media, 'accept' => 'image/x-icon,image/png']) ?>
                </div>
                <div class="col-md-6">
                    <label for="apple_touch_icon" class="form-label">Apple Touch Icon</label>
                    <?= view('admin/_media_selector', ['name' => 'apple_touch_icon', 'current' => $settings['apple_touch_icon'] ?? '', 'media' => $media, 'accept' => 'image/png']) ?>
                </div>
            </div>

            <h5 class="mb-3 mt-4">Tampilan Global</h5>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="show_preloader" name="show_preloader" value="1" <?= old('show_preloader', $settings['show_preloader'] ?? '1') == '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="show_preloader">Tampilkan preloader</label>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="show_scroll_to_top" name="show_scroll_to_top" value="1" <?= old('show_scroll_to_top', $settings['show_scroll_to_top'] ?? '1') == '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="show_scroll_to_top">Tampilkan tombol scroll ke atas</label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('admin') ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
