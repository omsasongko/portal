<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?= esc($settingsCount ?? 0) ?></h3>
                <p>Identitas Situs</p>
            </div>
            <i class="small-box-icon bi bi-globe"></i>
            <a href="<?= base_url('admin/settings/identity') ?>" class="small-box-footer">
                Lihat Detail <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= esc($enabledSectionsCount ?? 0) ?></h3>
                <p>Bagian Homepage &mdash; <?= esc($enabledSectionsCount ?? 0) ?> aktif</p>
            </div>
            <i class="small-box-icon bi bi-layout-text-window"></i>
            <a href="<?= base_url('admin/settings/hero') ?>" class="small-box-footer">
                Lihat Detail <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= esc($mediaCount ?? 0) ?></h3>
                <p>Media</p>
            </div>
            <i class="small-box-icon bi bi-images"></i>
            <a href="<?= base_url('admin') ?>" class="small-box-footer">
                Lihat Detail <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3><?= esc(($categoriesCount ?? 0) + ($tagsCount ?? 0)) ?></h3>
                <p>Kategori &amp; Tag</p>
            </div>
            <i class="small-box-icon bi bi-bookmark"></i>
            <a href="<?= base_url('admin/categories') ?>" class="small-box-footer">
                Lihat Detail <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
