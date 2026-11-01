<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>
<h1 class="h3 mb-4">Pengaturan Hero</h1>

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

        <form method="post" action="<?= base_url('admin/settings/hero') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="heading" class="form-label">Judul Hero</label>
                <input type="text" class="form-control <?= isset($errors['heading']) ? 'is-invalid' : '' ?>" id="heading" name="heading" value="<?= old('heading', $hero['heading'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="subheading" class="form-label">Deskripsi Hero</label>
                <textarea class="form-control <?= isset($errors['subheading']) ? 'is-invalid' : '' ?>" id="subheading" name="subheading" rows="3"><?= old('subheading', $hero['subheading'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label for="background_image" class="form-label">Gambar Latar</label>
                <?= view('admin/_media_selector', ['name' => 'background_image', 'current' => $hero['background_image'] ?? '', 'media' => $media, 'accept' => 'image/*']) ?>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="use_rotating_words" name="use_rotating_words" value="1" <?= old('use_rotating_words', ($hero['use_rotating_words'] ?? true) ? '1' : '0') == '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="use_rotating_words">Gunakan kata-kata berputar</label>
            </div>

            <div class="mb-3">
                <label for="rotating_words" class="form-label">Kata-kata Berputar</label>
                <textarea class="form-control" id="rotating_words" name="rotating_words" rows="5" placeholder="Web Developer&#10;Web Design"><?= old('rotating_words', isset($hero['rotating_words']) ? implode("\n", $hero['rotating_words']) : '') ?></textarea>
                <div class="form-text">Satu kata/frasa per baris.</div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="button_label" class="form-label">Label Tombol (opsional)</label>
                    <input type="text" class="form-control" id="button_label" name="button_label" value="<?= old('button_label', $hero['button_label'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="button_link" class="form-label">Link Tombol (opsional)</label>
                    <input type="text" class="form-control" id="button_link" name="button_link" value="<?= old('button_link', $hero['button_link'] ?? '') ?>">
                    <div class="form-text">Kosongkan jika tidak ingin menampilkan tombol.</div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('admin') ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
