<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title"><?= $edit ? 'Edit Item Menu' : 'Tambah Item Menu' ?></h3>
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

        <form method="post" action="<?= base_url('admin/menu/save') ?>">
            <?= csrf_field() ?>
            <?php if ($edit): ?>
                <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="label" class="form-label">Label Menu <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['label']) ? 'is-invalid' : '' ?>" id="label" name="label" value="<?= old('label', $edit['label'] ?? '') ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="type" class="form-label">Tipe Target <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>" id="type" name="type">
                        <option value="section" <?= old('type', $edit['type'] ?? '') === 'section' ? 'selected' : '' ?>>Bagian Homepage</option>
                        <option value="page" <?= old('type', $edit['type'] ?? '') === 'page' ? 'selected' : '' ?>>Halaman</option>
                        <option value="url" <?= old('type', $edit['type'] ?? '') === 'url' ? 'selected' : '' ?>>URL Eksternal</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3" id="target-section-wrap">
                    <label for="target_section" class="form-label">Bagian Homepage <span class="text-danger">*</span></label>
                    <select class="form-select" id="target_section" name="target_section">
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?= esc($sec) ?>" <?= old('target_section', ($edit && $edit['type'] === 'section') ? $edit['target'] : '') === $sec ? 'selected' : '' ?>><?= esc(ucfirst($sec)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-none" id="target-page-wrap">
                    <label for="target_page" class="form-label">Halaman <span class="text-danger">*</span></label>
                    <select class="form-select" id="target_page" name="target_page">
                        <option value="">— Pilih halaman —</option>
                        <?php foreach ($pages as $p): ?>
                            <option value="<?= esc($p['slug']) ?>" <?= old('target_page', ($edit && $edit['type'] === 'page') ? $edit['target'] : '') === $p['slug'] ? 'selected' : '' ?>><?= esc($p['title']) ?> (<?= esc($p['status']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-none" id="target-url-wrap">
                    <label for="target_url" class="form-label">URL <span class="text-danger">*</span></label>
                    <input type="url" class="form-control <?= isset($errors['target_url']) ? 'is-invalid' : '' ?>" id="target_url" name="target_url" value="<?= old('target_url', ($edit && $edit['type'] === 'url') ? $edit['target'] : '') ?>" placeholder="https://example.com">
                </div>
            </div>

            <?php if ($edit): ?>
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" value="1" <?= old('is_visible', $edit['is_visible'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_visible">Tampilkan di navigasi</label>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary"><?= $edit ? 'Simpan Perubahan' : 'Tambah Menu' ?></button>
            <?php if ($edit): ?>
                <a href="<?= base_url('admin/menu') ?>" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Daftar Menu (<?= count($items) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($items)): ?>
            <p class="text-muted">Belum ada item menu.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th style="width:60px;">Urutan</th>
                            <th>Label</th>
                            <th>Tipe</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th style="width:280px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                        <tr<?= ! $item['is_visible'] ? ' class="table-secondary"' : '' ?>>
                            <td><?= (int) $item['sort_order'] ?></td>
                            <td><?= esc($item['label']) ?></td>
                            <td>
                                <?php if ($item['type'] === 'section'): ?>
                                    <span class="badge text-bg-info">Bagian</span>
                                <?php elseif ($item['type'] === 'page'): ?>
                                    <span class="badge text-bg-primary">Halaman</span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">URL</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= esc($item['target']) ?></code></td>
                            <td>
                                <?php if ($item['is_visible']): ?>
                                    <span class="badge text-bg-success">Tampil</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Tersembunyi</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?= base_url('admin/menu/move-up') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Naik" <?= $i === 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                </form>
                                <form method="post" action="<?= base_url('admin/menu/move-down') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Turun" <?= $i === count($items) - 1 ? 'disabled' : '' ?>>
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                </form>
                                <form method="post" action="<?= base_url('admin/menu/toggle-visibility') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $item['is_visible'] ? 'btn-outline-warning' : 'btn-outline-success' ?>" title="<?= $item['is_visible'] ? 'Sembunyikan' : 'Tampilkan' ?>">
                                        <i class="bi <?= $item['is_visible'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                    </button>
                                </form>
                                <a href="<?= base_url('admin/menu?edit=' . (int) $item['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" action="<?= base_url('admin/menu/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus item menu ini? (Konten target tidak akan dihapus)');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
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
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const sectionWrap = document.getElementById('target-section-wrap');
    const pageWrap = document.getElementById('target-page-wrap');
    const urlWrap = document.getElementById('target-url-wrap');

    function toggleTargetFields() {
        const val = typeSelect.value;
        sectionWrap.classList.toggle('d-none', val !== 'section');
        pageWrap.classList.toggle('d-none', val !== 'page');
        urlWrap.classList.toggle('d-none', val !== 'url');
    }

    typeSelect.addEventListener('change', toggleTargetFields);
    toggleTargetFields();
});
</script>
<?php $this->endSection() ?>
