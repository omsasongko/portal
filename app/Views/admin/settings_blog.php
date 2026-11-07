<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>

<div class="alert alert-info">
    <h6 class="mb-1"><i class="bi bi-info-circle"></i> Pengaturan tata letak saling terpisah</h6>
    <ul class="mb-0 small">
        <li><strong>Urutan menu navigasi</strong> diatur di <a href="<?= base_url('admin/menu') ?>">Menu</a> (tombol naik/turun).</li>
        <li><strong>Urutan &amp; visibilitas bagian homepage</strong> (Hero, About, Layanan, dst.) diatur di masing-masing layar Pengaturan bagian tersebut.</li>
        <li><strong>Tata letak kartu Blog</strong> (2 atau 3 kolom di desktop) diatur di layar ini dan berlaku untuk Bagian Blog di homepage <em>dan</em> halaman daftar Blog (/blog).</li>
        <li><strong>Tata letak halaman detail artikel</strong> (lebar penuh atau dengan sidebar artikel terkait) dipilih per artikel di form Tambah/Edit Artikel.</li>
    </ul>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Bagian Blog di Homepage</h3>
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

        <form method="post" action="<?= base_url('admin/settings/blog') ?>">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="title" class="form-label">Judul Bagian <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= old('title', $blog['title'] ?? 'Blog') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="subtitle" class="form-label">Subjudul</label>
                    <input type="text" class="form-control <?= isset($errors['subtitle']) ? 'is-invalid' : '' ?>" id="subtitle" name="subtitle" value="<?= old('subtitle', $blog['subtitle'] ?? '') ?>" maxlength="255">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="limit" class="form-label">Jumlah Artikel di Homepage <span class="text-danger">*</span></label>
                    <input type="number" class="form-control <?= isset($errors['limit']) ? 'is-invalid' : '' ?>" id="limit" name="limit" min="1" max="12" value="<?= old('limit', $blog['limit'] ?? '3') ?>" required>
                    <div class="form-text">1&ndash;12 artikel terbaru yang berstatus Terbit. Saat ini ada <?= (int) $publishedCount ?> artikel terbit.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tata Letak Kartu <span class="text-danger">*</span></label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="card_layout" id="layout3" value="grid_3_columns" <?= old('card_layout', $blog['card_layout'] ?? 'grid_3_columns') === 'grid_3_columns' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="layout3">3 kolom (desktop)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="card_layout" id="layout2" value="grid_2_columns" <?= old('card_layout', $blog['card_layout'] ?? '') === 'grid_2_columns' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="layout2">2 kolom (desktop)</label>
                    </div>
                    <div class="form-text">
                        Berlaku untuk kartu Bagian Blog di homepage <strong>dan</strong> kartu di halaman daftar Blog (/blog).
                        Di layar kecil kedua halaman otomatis menjadi 1 kolom. Jumlah kartu homepage tetap diatur lewat
                        &ldquo;Jumlah Artikel di Homepage&rdquo;; halaman daftar selalu menampilkan 10 artikel terbit per halaman.
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?= old('is_enabled', $sectionEnabled) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_enabled">Tampilkan bagian Blog di homepage</label>
                </div>
                <div class="form-text">Jika dimatikan, bagian <code>#blog</code> disembunyikan dan tautan menu ke bagian ini otomatis dihilangkan dari navigasi publik.</div>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
            <a href="<?= base_url('admin/articles') ?>" class="btn btn-outline-secondary">Kelola Artikel</a>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
