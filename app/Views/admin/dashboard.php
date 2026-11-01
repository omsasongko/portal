<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<h1 class="h3 mb-4">Dasbor</h1>

<div class="row">
    <div class="col-md-4">
        <div class="card admin-card mb-4">
            <div class="card-body">
                <h5 class="card-title">Identitas Situs</h5>
                <p class="card-text display-6"><?= esc($settingsCount ?? 0) ?></p>
                <a href="<?= base_url('admin/settings/identity') ?>" class="btn btn-sm btn-primary">Kelola Identitas</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card admin-card mb-4">
            <div class="card-body">
                <h5 class="card-title">Bagian Homepage</h5>
                <p class="card-text display-6"><?= esc($heroCount ?? 0) ?></p>
                <a href="<?= base_url('admin/settings/hero') ?>" class="btn btn-sm btn-primary">Kelola Hero</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card admin-card mb-4">
            <div class="card-body">
                <h5 class="card-title">Media</h5>
                <p class="card-text display-6">0</p>
                <a href="#" class="btn btn-sm btn-secondary disabled">Belum Tersedia</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card admin-card mb-4">
            <div class="card-body">
                <h5 class="card-title">Artikel</h5>
                <p class="card-text display-6">0</p>
                <a href="#" class="btn btn-sm btn-secondary disabled">Belum Tersedia</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card admin-card mb-4">
            <div class="card-body">
                <h5 class="card-title">Kategori</h5>
                <p class="card-text display-6">0</p>
                <a href="#" class="btn btn-sm btn-secondary disabled">Belum Tersedia</a>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
