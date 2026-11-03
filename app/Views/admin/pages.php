<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Daftar Halaman (<?= count($pages) ?>)</h3>
        <a href="<?= base_url('admin/pages/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Halaman
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($pages)): ?>
            <p class="text-muted">Belum ada halaman.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Diperbarui</th>
                            <th style="width:220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $p): ?>
                        <tr>
                            <td><?= esc($p['title']) ?></td>
                            <td><code><?= esc($p['slug']) ?></code></td>
                            <td>
                                <?php if ($p['status'] === 'published'): ?>
                                    <span class="badge text-bg-success">Terbit</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Draf</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($p['updated_at'] ?? $p['created_at'] ?? '-') ?></td>
                            <td>
                                <?php if ($p['status'] === 'published'): ?>
                                    <a href="<?= base_url('pages/' . $p['slug']) ?>" class="btn btn-sm btn-outline-info" target="_blank" title="Lihat">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= base_url('admin/pages/edit/' . (int) $p['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" action="<?= base_url('admin/pages/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus halaman ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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
