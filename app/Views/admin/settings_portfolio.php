<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Bagian Portofolio</h3>
    </div>
    <div class="card-body">
        <form method="post" action="<?= base_url('admin/settings/portfolio') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="title" class="form-label">Judul Bagian</label>
                <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= old('title', $portfolio['title'] ?? 'Portfolio') ?>">
            </div>

            <div class="mb-3">
                <label for="subtitle" class="form-label">Deskripsi Bagian</label>
                <textarea class="form-control <?= isset($errors['subtitle']) ? 'is-invalid' : '' ?>" id="subtitle" name="subtitle" rows="2"><?= old('subtitle', $portfolio['subtitle'] ?? '') ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="layout" class="form-label">Tata Letak Kolom</label>
                    <select class="form-select" id="layout" name="layout">
                        <option value="masonry_3_columns" <?= old('layout', $portfolio['layout'] ?? 'masonry_3_columns') === 'masonry_3_columns' ? 'selected' : '' ?>>3 Kolom</option>
                        <option value="grid_2_columns" <?= old('layout', $portfolio['layout'] ?? '') === 'grid_2_columns' ? 'selected' : '' ?>>2 Kolom</option>
                    </select>
                </div>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?= old('is_enabled', $sectionEnabled ? '1' : '0') == '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_enabled">Tampilkan bagian Portofolio</label>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Bagian</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title"><?= $editCategory ? 'Edit Kategori' : 'Tambah Kategori' ?></h3>
    </div>
    <div class="card-body">
        <?php if (isset($errors['name']) || isset($errors['filter_class'])): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $k => $e): ?>
                        <?php if (in_array($k, ['name', 'filter_class'], true)): ?>
                            <li><?= esc($e) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('admin/settings/portfolio/category') ?>" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <?php if ($editCategory): ?>
                <input type="hidden" name="category_id" value="<?= (int) $editCategory['id'] ?>">
            <?php endif; ?>
            <div class="col-md-4">
                <label for="category_name" class="form-label">Nama Kategori</label>
                <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="category_name" name="name" value="<?= old('name', $editCategory['name'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label for="filter_class" class="form-label">Kelas Filter (CSS)</label>
                <input type="text" class="form-control <?= isset($errors['filter_class']) ? 'is-invalid' : '' ?>" id="filter_class" name="filter_class" value="<?= old('filter_class', $editCategory['filter_class'] ?? '') ?>" placeholder="contoh: gal_d">
                <div class="form-text">Awali huruf; hanya huruf, angka, garis bawah, atau tanda hubung.</div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><?= $editCategory ? 'Simpan Perubahan' : 'Tambah Kategori' ?></button>
                <?php if ($editCategory): ?>
                    <a href="<?= base_url('admin/settings/portfolio') ?>" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </div>
        </form>

        <hr>

        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kelas Filter</th>
                    <th>Jumlah Item</th>
                    <th style="width:180px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="4" class="text-muted">Belum ada kategori.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= esc($cat['name']) ?></td>
                        <td><code><?= esc($cat['filter_class']) ?></code></td>
                        <td><?= (int) $cat['item_count'] ?></td>
                        <td>
                            <a href="<?= base_url('admin/settings/portfolio?edit_cat=' . (int) $cat['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?= base_url('admin/settings/portfolio/category/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus kategori ini?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="category_id" value="<?= (int) $cat['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title"><?= $editItem ? 'Edit Item Portofolio' : 'Tambah Item Portofolio' ?></h3>
    </div>
    <div class="card-body">
        <?php if (isset($errors['item_image']) || isset($errors['title']) || isset($errors['image_alt']) || isset($errors['link']) || isset($errors['categories'])): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $k => $e): ?>
                        <?php if (in_array($k, ['item_image', 'title', 'image_alt', 'link', 'categories'], true)): ?>
                            <li><?= esc($e) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('admin/settings/portfolio/item') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php if ($editItem): ?>
                <input type="hidden" name="item_id" value="<?= (int) $editItem['id'] ?>">
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="item_image" class="form-label">Gambar</label>
                    <?= view('admin/_media_selector', ['name' => 'item_image', 'current' => $editItem['image'] ?? '', 'media' => $media, 'accept' => 'image/*']) ?>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="item_title" class="form-label">Judul (opsional)</label>
                        <input type="text" class="form-control" id="item_title" name="title" value="<?= old('title', $editItem['title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="image_alt" class="form-label">Teks Alternatif Gambar</label>
                        <input type="text" class="form-control" id="image_alt" name="image_alt" value="<?= old('image_alt', $editItem['image_alt'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="link" class="form-label">Link (opsional)</label>
                        <input type="text" class="form-control" id="link" name="link" value="<?= old('link', $editItem['link'] ?? '') ?>">
                        <div class="form-text">Kosongkan untuk lightbox foto.</div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Kategori</label>
                <?php if (empty($categories)): ?>
                    <p class="text-muted">Tambahkan kategori terlebih dahulu.</p>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $checked = in_array((int) $cat['id'], $editItem['category_ids'] ?? old('categories', []), true);
                        ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="categories[]" id="cat_<?= (int) $cat['id'] ?>" value="<?= (int) $cat['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                            <label class="form-check-label" for="cat_<?= (int) $cat['id'] ?>"><?= esc($cat['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary"><?= $editItem ? 'Simpan Perubahan' : 'Tambah Item' ?></button>
            <?php if ($editItem): ?>
                <a href="<?= base_url('admin/settings/portfolio') ?>" class="btn btn-secondary">Batal Edit</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Daftar Item (<?= count($items) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($items)): ?>
            <p class="text-muted">Belum ada item portofolio.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th style="width:70px;">Urutan</th>
                            <th>Gambar</th>
                            <th>Judul / Alt</th>
                            <th>Kategori</th>
                            <th style="width:220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total = count($items); foreach ($items as $i => $item): ?>
                        <?php
                        $catNames = [];
                        foreach ($categories as $cat) {
                            if (in_array((int) $cat['id'], $item['category_ids'], true)) {
                                $catNames[] = $cat['name'];
                            }
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="btn-group-vertical btn-group-sm w-100">
                                    <form method="post" action="<?= base_url('admin/settings/portfolio/item/reorder') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <?php
                                        $up = $items;
                                        if ($i > 0) { $t = $up[$i - 1]; $up[$i - 1] = $up[$i]; $up[$i] = $t; }
                                        foreach ($up as $r): ?><input type="hidden" name="order[]" value="<?= (int) $r['id'] ?>"><?php endforeach; ?>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" <?= $i === 0 ? 'disabled' : '' ?> title="Naik">&uarr;</button>
                                    </form>
                                    <form method="post" action="<?= base_url('admin/settings/portfolio/item/reorder') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <?php
                                        $down = $items;
                                        if ($i < $total - 1) { $t = $down[$i + 1]; $down[$i + 1] = $down[$i]; $down[$i] = $t; }
                                        foreach ($down as $r): ?><input type="hidden" name="order[]" value="<?= (int) $r['id'] ?>"><?php endforeach; ?>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" <?= $i === $total - 1 ? 'disabled' : '' ?> title="Turun">&darr;</button>
                                    </form>
                                </div>
                            </td>
                            <td><img src="<?= base_url($item['image']) ?>" alt="" style="height:50px; object-fit:cover;" class="rounded"></td>
                            <td>
                                <div><?= esc($item['title'] ?: '(tanpa judul)') ?></div>
                                <small class="text-muted"><?= esc($item['image_alt'] ?? '') ?></small>
                            </td>
                            <td><?= esc(implode(', ', $catNames)) ?></td>
                            <td>
                                <a href="<?= base_url('admin/settings/portfolio?edit_item=' . (int) $item['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" action="<?= base_url('admin/settings/portfolio/item/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus item portofolio ini?');">
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
