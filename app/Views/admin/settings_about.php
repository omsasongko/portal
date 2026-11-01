<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>
<h1 class="h3 mb-4">Pengaturan About</h1>

<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="post" action="<?= base_url('admin/settings/about') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="heading" class="form-label">Judul</label>
                <input type="text" class="form-control <?= isset($errors['heading']) ? 'is-invalid' : '' ?>" id="heading" name="heading" value="<?= old('heading', $about['heading'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="body" class="form-label">Isi</label>
                <textarea class="form-control <?= isset($errors['body']) ? 'is-invalid' : '' ?>" id="body" name="body" rows="6"><?= old('body', $about['body'] ?? '') ?></textarea>
                <div class="form-text">Pisahkan paragraf dengan baris kosong.</div>
            </div>

            <div class="mb-3">
                <label for="about_image" class="form-label">Gambar</label>
                <?= view('admin/_media_selector', ['name' => 'about_image', 'current' => $about['image'] ?? '', 'media' => $media, 'accept' => 'image/*']) ?>
            </div>

            <div class="mb-3">
                <label for="image_alt" class="form-label">Teks Alternatif Gambar</label>
                <input type="text" class="form-control" id="image_alt" name="image_alt" value="<?= old('image_alt', $about['image_alt'] ?? '') ?>">
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="button_label" class="form-label">Label Tombol</label>
                    <input type="text" class="form-control" id="button_label" name="button_label" value="<?= old('button_label', $about['button_label'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="button_link" class="form-label">Link Tombol</label>
                    <input type="text" class="form-control" id="button_link" name="button_link" value="<?= old('button_link', $about['button_link'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="layout" class="form-label">Tata Letak Gambar</label>
                <select class="form-select" id="layout" name="layout">
                    <option value="image_right" <?= old('layout', $about['layout'] ?? 'image_right') === 'image_right' ? 'selected' : '' ?>>Gambar di Kanan</option>
                    <option value="image_left" <?= old('layout', $about['layout'] ?? '') === 'image_left' ? 'selected' : '' ?>>Gambar di Kiri</option>
                    <option value="text_only" <?= old('layout', $about['layout'] ?? '') === 'text_only' ? 'selected' : '' ?>>Teks Saja</option>
                </select>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?= old('is_enabled', ($about['is_enabled'] ?? true) ? '1' : '0') == '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_enabled">Tampilkan bagian About</label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('admin') ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
