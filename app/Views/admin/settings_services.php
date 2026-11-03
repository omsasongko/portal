<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>

<?php if (session()->has('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Bagian Layanan</h3>
    </div>
    <div class="card-body">
        <form method="post" action="<?= base_url('admin/settings/services') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="title" class="form-label">Judul Bagian</label>
                <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= old('title', $services['title'] ?? 'Services') ?>">
            </div>

            <div class="mb-3">
                <label for="subtitle" class="form-label">Deskripsi Bagian</label>
                <textarea class="form-control <?= isset($errors['subtitle']) ? 'is-invalid' : '' ?>" id="subtitle" name="subtitle" rows="2"><?= old('subtitle', $services['subtitle'] ?? '') ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="layout" class="form-label">Tata Letak Kolom</label>
                    <select class="form-select" id="layout" name="layout">
                        <option value="grid_3_columns" <?= old('layout', $services['layout'] ?? 'grid_3_columns') === 'grid_3_columns' ? 'selected' : '' ?>>3 Kolom</option>
                        <option value="grid_2_columns" <?= old('layout', $services['layout'] ?? '') === 'grid_2_columns' ? 'selected' : '' ?>>2 Kolom</option>
                    </select>
                </div>
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?= old('is_enabled', ($sectionEnabled ?? true) ? '1' : '0') == '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_enabled">Tampilkan bagian Layanan</label>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Bagian</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title"><?= $edit ? 'Edit Kartu Layanan' : 'Tambah Kartu Layanan' ?></h3>
    </div>
    <div class="card-body">
        <?php if (isset($errors['icon']) || isset($errors['card_title']) || isset($errors['description'])): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $k => $e): ?>
                        <?php if (in_array($k, ['icon', 'card_title', 'description'], true)): ?>
                            <li><?= esc($e) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('admin/settings/services/card') ?>">
            <?= csrf_field() ?>
            <?php if ($edit): ?>
                <input type="hidden" name="card_id" value="<?= (int) $edit['id'] ?>">
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="icon" class="form-label">Ikon</label>
                    <select class="form-select <?= isset($errors['icon']) ? 'is-invalid' : '' ?>" id="icon" name="icon">
                        <?php foreach ($icons as $class => $label): ?>
                            <option value="<?= esc($class) ?>" <?= old('icon', $edit['icon'] ?? '') === $class ? 'selected' : '' ?>><?= esc($label) ?> (<?= esc($class) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="card_title" class="form-label">Judul Kartu</label>
                    <input type="text" class="form-control <?= isset($errors['card_title']) ? 'is-invalid' : '' ?>" id="card_title" name="card_title" value="<?= old('card_title', $edit['title'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Pratinjau Ikon</label>
                    <div class="border rounded p-2 text-center text-muted">
                        <i id="icon-preview" class="<?= esc(old('icon', $edit['icon'] ?? array_key_first($icons))) ?>" style="font-size:2rem;"></i>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="3"><?= old('description', $edit['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?= $edit ? 'Simpan Perubahan' : 'Tambah Kartu' ?></button>
            <?php if ($edit): ?>
                <a href="<?= base_url('admin/settings/services') ?>" class="btn btn-secondary">Batal Edit</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Daftar Kartu (<?= count($items) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($items)): ?>
            <p class="text-muted">Belum ada kartu layanan.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th style="width:70px;">Urutan</th>
                            <th>Ikon</th>
                            <th>Judul</th>
                            <th>Deskripsi</th>
                            <th style="width:220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total = count($items); foreach ($items as $i => $item): ?>
                        <?php $d = $item['data']; ?>
                        <tr>
                            <td>
                                <div class="btn-group-vertical btn-group-sm w-100">
                                    <form method="post" action="<?= base_url('admin/settings/services/reorder') ?>" class="d-inline">
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
                                    <form method="post" action="<?= base_url('admin/settings/services/reorder') ?>" class="d-inline">
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
                            <td><i class="<?= esc($d['icon'] ?? '') ?>" style="font-size:1.5rem;"></i></td>
                            <td><?= esc($d['title'] ?? '') ?></td>
                            <td><?= esc($d['description'] ?? '') ?></td>
                            <td>
                                <a href="<?= base_url('admin/settings/services?edit=' . (int) $item['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" action="<?= base_url('admin/settings/services/card/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus kartu layanan ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="card_id" value="<?= (int) $item['id'] ?>">
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

<?php $this->section('scripts') ?>
<script>
document.getElementById('icon').addEventListener('change', function () {
    document.getElementById('icon-preview').className = this.value;
});
</script>
<?php $this->endSection() ?>
