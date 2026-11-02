<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>
<h1 class="h3 mb-4">Pengaturan Testimoni</h1>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="card admin-card mb-4">
    <div class="card-body">
        <h5 class="mb-3">Bagian Testimoni</h5>
        <form method="post" action="<?= base_url('admin/settings/testimonials') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="title" class="form-label">Judul Bagian</label>
                <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= old('title', $testimonials['title'] ?? 'Testimonials') ?>">
            </div>

            <div class="mb-3">
                <label for="subtitle" class="form-label">Deskripsi Bagian</label>
                <textarea class="form-control <?= isset($errors['subtitle']) ? 'is-invalid' : '' ?>" id="subtitle" name="subtitle" rows="2"><?= old('subtitle', $testimonials['subtitle'] ?? '') ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="layout" class="form-label">Jumlah per Slide</label>
                    <select class="form-select" id="layout" name="layout">
                        <option value="carousel_2_per_slide" <?= old('layout', $testimonials['layout'] ?? 'carousel_2_per_slide') === 'carousel_2_per_slide' ? 'selected' : '' ?>>2 per slide</option>
                        <option value="carousel_1_per_slide" <?= old('layout', $testimonials['layout'] ?? '') === 'carousel_1_per_slide' ? 'selected' : '' ?>>1 per slide</option>
                    </select>
                </div>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?= old('is_enabled', ($sectionEnabled ?? true) ? '1' : '0') == '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_enabled">Tampilkan bagian Testimoni</label>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Bagian</button>
        </form>
    </div>
</div>

<div class="card admin-card mb-4">
    <div class="card-body">
        <h5 class="mb-3"><?= $edit ? 'Edit Testimoni' : 'Tambah Testimoni' ?></h5>
        <?php if (isset($errors['quote_heading']) || isset($errors['quote_body']) || isset($errors['author_name']) || isset($errors['author_role']) || isset($errors['avatar'])): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $k => $e): ?>
                        <?php if (in_array($k, ['quote_heading', 'quote_body', 'author_name', 'author_role', 'avatar'], true)): ?>
                            <li><?= esc($e) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('admin/settings/testimonials/card') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php if ($edit): ?>
                <input type="hidden" name="item_id" value="<?= (int) $edit['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="quote_heading" class="form-label">Kutipan (Judul)</label>
                <input type="text" class="form-control <?= isset($errors['quote_heading']) ? 'is-invalid' : '' ?>" id="quote_heading" name="quote_heading" value="<?= old('quote_heading', $edit['quote_heading'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="quote_body" class="form-label">Isi Kutipan <span class="text-danger">*</span></label>
                <textarea class="form-control <?= isset($errors['quote_body']) ? 'is-invalid' : '' ?>" id="quote_body" name="quote_body" rows="3"><?= old('quote_body', $edit['quote_body'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="author_name" class="form-label">Nama Penulis <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['author_name']) ? 'is-invalid' : '' ?>" id="author_name" name="author_name" value="<?= old('author_name', $edit['author_name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="author_role" class="form-label">Peran / Jabatan</label>
                    <input type="text" class="form-control <?= isset($errors['author_role']) ? 'is-invalid' : '' ?>" id="author_role" name="author_role" value="<?= old('author_role', $edit['author_role'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Avatar</label>
                <?= view('admin/_media_selector', ['name' => 'avatar', 'current' => $edit['avatar'] ?? '', 'media' => $media, 'accept' => 'image/*']) ?>
            </div>

            <button type="submit" class="btn btn-primary"><?= $edit ? 'Simpan Perubahan' : 'Tambah Testimoni' ?></button>
            <?php if ($edit): ?>
                <a href="<?= base_url('admin/settings/testimonials') ?>" class="btn btn-secondary">Batal Edit</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card admin-card mb-4">
    <div class="card-body">
        <h5 class="mb-3">Daftar Testimoni (<?= count($items) ?>)</h5>
        <?php if (empty($items)): ?>
            <p class="text-muted">Belum ada testimoni.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:70px;">Urutan</th>
                            <th style="width:70px;">Avatar</th>
                            <th>Kutipan</th>
                            <th>Penulis</th>
                            <th style="width:220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total = count($items); foreach ($items as $i => $item): ?>
                        <?php $d = $item['data']; ?>
                        <tr>
                            <td>
                                <div class="btn-group-vertical btn-group-sm w-100">
                                    <form method="post" action="<?= base_url('admin/settings/testimonials/reorder') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <?php
                                        $up = $items;
                                        if ($i > 0) {
                                            $tmp = $up[$i - 1];
                                            $up[$i - 1] = $up[$i];
                                            $up[$i] = $tmp;
                                        }
                                        foreach ($up as $r): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $r['id'] ?>">
                                        <?php endforeach; ?>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" <?= $i === 0 ? 'disabled' : '' ?> title="Naik">&uarr;</button>
                                    </form>
                                    <form method="post" action="<?= base_url('admin/settings/testimonials/reorder') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <?php
                                        $down = $items;
                                        if ($i < $total - 1) {
                                            $tmp = $down[$i + 1];
                                            $down[$i + 1] = $down[$i];
                                            $down[$i] = $tmp;
                                        }
                                        foreach ($down as $r): ?>
                                            <input type="hidden" name="order[]" value="<?= (int) $r['id'] ?>">
                                        <?php endforeach; ?>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" <?= $i === $total - 1 ? 'disabled' : '' ?> title="Turun">&darr;</button>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <?php if (! empty($d['avatar'])): ?>
                                    <img src="<?= base_url($d['avatar']) ?>" alt="" style="height:44px; width:44px; object-fit:cover;" class="rounded-circle">
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><strong><?= esc($d['quote_heading'] ?? '') ?></strong></div>
                                <small class="text-muted"><?= esc($d['quote_body'] ?? '') ?></small>
                            </td>
                            <td>
                                <div><?= esc($d['author_name'] ?? '') ?></div>
                                <small class="text-muted"><?= esc($d['author_role'] ?? '') ?></small>
                            </td>
                            <td>
                                <a href="<?= base_url('admin/settings/testimonials?edit=' . (int) $item['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" action="<?= base_url('admin/settings/testimonials/card/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus testimoni ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection() ?>