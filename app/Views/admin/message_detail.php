<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>

<?php
    $wib = '';
    if ($msg['created_at']) {
        $dt = new \DateTime($msg['created_at'], new \DateTimeZone('UTC'));
        $dt->setTimezone(new \DateTimeZone('Asia/Jakarta'));
        $wib = $dt->format('d M Y H:i') . ' WIB';
    }
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 class="card-title mb-0">
            Pesan dari <?= esc($msg['name']) ?>
            <?php if ($msg['is_read']): ?>
                <span class="badge text-bg-secondary">Sudah Dibaca</span>
            <?php else: ?>
                <span class="badge text-bg-primary">Belum Dibaca</span>
            <?php endif; ?>
        </h3>
        <a href="<?= base_url('admin/messages') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Nama</dt>
            <dd class="col-sm-9"><?= esc($msg['name']) ?></dd>

            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><a href="mailto:<?= esc($msg['email']) ?>"><?= esc($msg['email']) ?></a></dd>

            <dt class="col-sm-3">Telepon / WhatsApp</dt>
            <dd class="col-sm-9"><?= $msg['phone'] ? esc($msg['phone']) : '<span class="text-muted">Tidak diisi</span>' ?></dd>

            <dt class="col-sm-3">Waktu</dt>
            <dd class="col-sm-9"><?= esc($wib) ?></dd>

            <dt class="col-sm-3">IP Address</dt>
            <dd class="col-sm-9"><code><?= esc($msg['ip_address']) ?></code></dd>

            <dt class="col-sm-3">Pesan</dt>
            <dd class="col-sm-9">
                <div class="border rounded p-3 bg-light" style="white-space: pre-wrap;"><?= esc($msg['message']) ?></div>
            </dd>
        </dl>
    </div>
    <div class="card-footer d-flex gap-2">
        <form method="post" action="<?= base_url('admin/messages/toggle-read') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $msg['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-<?= $msg['is_read'] ? 'envelope' : 'envelope-open' ?>"></i>
                <?= $msg['is_read'] ? 'Tandai Belum Dibaca' : 'Tandai Sudah Dibaca' ?>
            </button>
        </form>
        <form method="post" action="<?= base_url('admin/messages/delete') ?>" onsubmit="return confirm('Hapus pesan dari <?= esc(addslashes($msg['name'])) ?>?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $msg['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </form>
    </div>
</div>

<?php $this->endSection() ?>
