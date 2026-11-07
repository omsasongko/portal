<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Pesan Masuk <?php if ($unread): ?><span class="badge text-bg-danger"><?= $unread ?> belum dibaca</span><?php endif; ?></h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($messages)): ?>
            <p class="text-muted p-3 mb-0">Belum ada pesan.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:40px"></th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Pesan</th>
                            <th>Waktu (WIB)</th>
                            <th style="width:180px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $m): ?>
                        <?php
                            $rowClass = $m['is_read'] ? '' : 'fw-bold';
                            $wib = '';
                            if ($m['created_at']) {
                                $dt = new \DateTime($m['created_at'], new \DateTimeZone('UTC'));
                                $dt->setTimezone(new \DateTimeZone('Asia/Jakarta'));
                                $wib = $dt->format('d M Y H:i');
                            }
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <?php if (! $m['is_read']): ?>
                                    <span class="badge text-bg-primary" title="Belum dibaca">&bull;</span>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($m['name']) ?></td>
                            <td><a href="mailto:<?= esc($m['email']) ?>"><?= esc($m['email']) ?></a></td>
                            <td><?= $m['phone'] ? esc($m['phone']) : '<span class="text-muted">-</span>' ?></td>
                            <td><?= esc(mb_strimwidth($m['message'], 0, 80, '…')) ?></td>
                            <td class="text-nowrap"><?= esc($wib) ?></td>
                            <td>
                                <a href="<?= base_url('admin/messages/show/' . (int) $m['id']) ?>" class="btn btn-sm btn-outline-primary" title="Lihat">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form method="post" action="<?= base_url('admin/messages/toggle-read') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= $m['is_read'] ? 'Tandai belum dibaca' : 'Tandai sudah dibaca' ?>">
                                        <i class="bi bi-<?= $m['is_read'] ? 'envelope' : 'envelope-open' ?>"></i>
                                    </button>
                                </form>
                                <form method="post" action="<?= base_url('admin/messages/delete') ?>" class="d-inline" onsubmit="return confirm('Hapus pesan dari <?= esc(addslashes($m['name'])) ?>?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pager): ?>
                <div class="card-footer">
                    <?= $pager->links('default', 'default_full') ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection() ?>
