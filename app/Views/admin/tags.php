<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>
<h1 class="h3 mb-4">Tag Artikel</h1>

<div class="card admin-card mb-4">
    <div class="card-body">
        <h5 class="mb-3"><?= $edit ? 'Edit Tag' : 'Tambah Tag' ?></h5>
        <?php if (isset($errors['name']) || isset($errors['slug']) || isset($errors['description'])): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $k => $e): ?>
                        <?php if (in_array($k, ['name', 'slug', 'description'], true)): ?>
                            <li><?= esc($e) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= base_url('admin/tags/save') ?>">
            <?= csrf_field() ?>
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Nama Tag <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= old('name', $edit['name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" id="slug" name="slug" value="<?= old('slug', $edit['slug'] ?? '') ?>" placeholder="otomatis dari nama bila kosong">
                    <div class="form-text">Huruf kecil, angka, dan tanda hubung. Mengubah nama tidak otomatis mengubah slug yang sudah tersimpan.</div>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi (opsional)</label>
                <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="2"><?= old('description', $edit['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?= $edit ? 'Simpan Perubahan' : 'Tambah Tag' ?></button>
            <?php if ($edit): ?>
                <a href="<?= base_url('admin/tags') ?>" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card admin-card mb-4">
    <div class="card-body">
        <h5 class="mb-3">Daftar Tag (<?= count($tags) ?>)</h5>
        <?php if (empty($tags)): ?>
            <p class="text-muted">Belum ada tag.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Slug</th>
                            <th>Deskripsi</th>
                            <th style="width:180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td><?= esc($tag['name']) ?></td>
                            <td><code><?= esc($tag['slug']) ?></code></td>
                            <td><?= esc($tag['description'] ?? '') ?></td>
                            <td>
                                <a href="<?= base_url('admin/tags?edit=' . (int) $tag['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" action="<?= base_url('admin/tags/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus tag ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $tag['id'] ?>">
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