<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Daftar Artikel (<?= count($articles) ?>)</h3>
        <a href="<?= base_url('admin/articles/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Artikel
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($articles)): ?>
            <p class="text-muted">Belum ada artikel.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Slug</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Tanggal Terbit</th>
                            <th style="width:240px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $a): ?>
                        <tr>
                            <td><?= esc($a['title']) ?></td>
                            <td><code><?= esc($a['slug']) ?></code></td>
                            <td><?= esc($catNames[$a['category_id']] ?? '-') ?></td>
                            <td>
                                <?php if ($a['status'] === 'published'): ?>
                                    <span class="badge text-bg-success">Terbit</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">Draf</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($a['published_at'] ?? '-') ?></td>
                            <td>
                                <a href="<?= base_url('admin/articles/preview/' . (int) $a['id']) ?>" class="btn btn-sm btn-outline-info" target="_blank" title="Pratinjau">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($a['status'] === 'published'): ?>
                                    <a href="<?= base_url('blog/' . $a['slug']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Lihat di situs">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= base_url('admin/articles/edit/' . (int) $a['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post" action="<?= base_url('admin/articles/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus artikel ini? Media sampul tidak ikut dihapus.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
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
