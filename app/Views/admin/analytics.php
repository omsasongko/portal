<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Analitik Pengunjung <small class="text-muted">(Waktu WIB)</small></h3>
        <form method="get" action="<?= base_url('admin/analytics') ?>" class="d-flex align-items-center gap-2 flex-wrap">
            <label for="from" class="form-label mb-0 small">Dari</label>
            <input type="date" id="from" name="from" class="form-control form-control-sm" value="<?= esc($from) ?>" style="width:auto">
            <label for="to" class="form-label mb-0 small">Sampai</label>
            <input type="date" id="to" name="to" class="form-control form-control-sm" value="<?= esc($to) ?>" style="width:auto">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-filter"></i> Terapkan</button>
        </form>
    </div>
</div>

<!-- Total visits -->
<div class="row mb-4">
    <div class="col-lg-4 col-sm-6">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3><?= number_format($totalVisits) ?></h3>
                <p>Total Kunjungan</p>
            </div>
            <i class="small-box-icon bi bi-eye"></i>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= count($topPages) ?></h3>
                <p>Halaman Dikunjungi</p>
            </div>
            <i class="small-box-icon bi bi-file-earmark-text"></i>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= count($frequentIps) ?></h3>
                <p>IP Frekuensi Tinggi (≥20)</p>
            </div>
            <i class="small-box-icon bi bi-exclamation-triangle"></i>
        </div>
    </div>
</div>

<!-- Daily visits chart (simple bar via CSS) -->
<?php if ($dailyCounts): ?>
<div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Kunjungan Harian (WIB)</h3></div>
    <div class="card-body" style="overflow-x:auto">
        <?php
        $maxDaily = max(array_column($dailyCounts, 'visits'));
        ?>
        <div class="d-flex align-items-end gap-1" style="min-height:150px; min-width:<?= count($dailyCounts) * 28 ?>px">
            <?php foreach ($dailyCounts as $day): ?>
                <?php $pct = $maxDaily > 0 ? round($day['visits'] / $maxDaily * 100) : 0; ?>
                <div class="text-center flex-fill" style="min-width:24px" title="<?= esc($day['date']) ?>: <?= number_format($day['visits']) ?> kunjungan">
                    <small class="d-block text-muted" style="font-size:0.65rem"><?= (int) $day['visits'] ?></small>
                    <div class="bg-primary rounded-top" style="height:<?= max($pct, 2) ?>px; min-height:2px"></div>
                    <small class="d-block text-muted" style="font-size:0.6rem"><?= date('d', strtotime($day['date'])) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Top pages -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Halaman Terpopuler</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm mb-0">
                    <thead><tr><th>#</th><th>Halaman</th><th>Tipe</th><th class="text-end">Kunjungan</th></tr></thead>
                    <tbody>
                    <?php if (empty($topPages)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data</td></tr>
                    <?php else: ?>
                        <?php foreach ($topPages as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><code><?= esc($row['page_path']) ?></code></td>
                            <td><?php
                                $typeLabels = ['home' => 'Home', 'section' => 'Section', 'blog' => 'Blog', 'article' => 'Article', 'page' => 'Page'];
                                $typeColors = ['home' => 'info', 'section' => 'dark', 'blog' => 'success', 'article' => 'primary', 'page' => 'secondary'];
                                $label = $typeLabels[$row['page_type']] ?? $row['page_type'];
                                $color = $typeColors[$row['page_type']] ?? 'secondary';
                            ?><span class="badge text-bg-<?= $color ?>"><?= esc($label) ?></span></td>
                            <td class="text-end"><?= number_format($row['visits']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Browser + Device breakdown -->
    <div class="col-lg-6 mb-4">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Browser</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm mb-0">
                    <thead><tr><th>Browser</th><th class="text-end">Kunjungan</th></tr></thead>
                    <tbody>
                    <?php if (empty($browserBreakdown)): ?>
                        <tr><td colspan="2" class="text-center text-muted py-3">Belum ada data</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($browserBreakdown, 0, 10) as $row): ?>
                        <tr>
                            <td><?= esc($row['browser']) ?></td>
                            <td class="text-end"><?= number_format($row['visits']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Perangkat</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped table-sm mb-0">
                    <thead><tr><th>Tipe Perangkat</th><th class="text-end">Kunjungan</th></tr></thead>
                    <tbody>
                    <?php if (empty($deviceBreakdown)): ?>
                        <tr><td colspan="2" class="text-center text-muted py-3">Belum ada data</td></tr>
                    <?php else: ?>
                        <?php foreach ($deviceBreakdown as $row): ?>
                        <tr>
                            <td>
                                <?php
                                $deviceLabels = ['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet', 'other' => 'Lainnya'];
                                echo esc($deviceLabels[$row['device_type']] ?? $row['device_type']);
                                ?>
                            </td>
                            <td class="text-end"><?= number_format($row['visits']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Frequent IPs (review aid) -->
<?php if ($frequentIps): ?>
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-exclamation-triangle text-warning"></i> IP Frekuensi Tinggi</h3>
        <small class="text-muted ms-2">Indikator untuk peninjauan — IP bisa dipakai bersama; jangan blokir tanpa investigasi.</small>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-sm mb-0">
            <thead><tr><th>IP Address</th><th class="text-end">Kunjungan</th><th class="text-end">Halaman Unik</th><th>Pertama (WIB)</th><th>Terakhir (WIB)</th></tr></thead>
            <tbody>
            <?php foreach ($frequentIps as $row): ?>
            <tr>
                <td><code><?= esc($row['ip_address']) ?></code></td>
                <td class="text-end"><?= number_format($row['visits']) ?></td>
                <td class="text-end"><?= number_format($row['distinct_pages']) ?></td>
                <td><?= esc($row['first_visit']) ?></td>
                <td><?= esc($row['last_visit']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Recent visits -->
<div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Kunjungan Terakhir</h3></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-sm mb-0">
                <thead><tr><th>Waktu (WIB)</th><th>Halaman</th><th>Tipe</th><th>IP</th><th>Lokasi (Perkiraan)</th><th>Browser</th><th>OS</th><th>Perangkat</th><th>Bot</th></tr></thead>
                <tbody>
                <?php if (empty($recentVisits)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-3">Belum ada data</td></tr>
                <?php else: ?>
                    <?php foreach ($recentVisits as $row): ?>
                    <tr>
                        <td class="text-nowrap"><?= esc($row['created_at']) ?></td>
                        <td><code><?= esc($row['page_path']) ?></code></td>
                        <td><?php
                            $rtLabels = ['home' => 'Home', 'section' => 'Section', 'blog' => 'Blog', 'article' => 'Article', 'page' => 'Page'];
                            $rtColors = ['home' => 'info', 'section' => 'dark', 'blog' => 'success', 'article' => 'primary', 'page' => 'secondary'];
                            $rtLabel = $rtLabels[$row['page_type']] ?? $row['page_type'];
                            $rtColor = $rtColors[$row['page_type']] ?? 'secondary';
                        ?><span class="badge text-bg-<?= $rtColor ?>"><?= esc($rtLabel) ?></span></td>
                        <td><code><?= esc($row['ip_address']) ?></code></td>
                        <td class="text-nowrap"><?php
                            $loc = \App\Libraries\GeoIpLookup::formatLocation([
                                'country'    => $row['country'] ?? '',
                                'region'     => $row['region'] ?? '',
                                'city'       => $row['city'] ?? '',
                                'is_private' => ($row['country'] === 'Lokal/Jaringan Privat'),
                            ]);
                            echo esc($loc);
                        ?></td>
                        <td><?= esc($row['browser']) ?></td>
                        <td><?= esc($row['os']) ?></td>
                        <td><?= esc($row['device_type']) ?></td>
                        <td><?= $row['is_bot'] ? '<span class="badge text-bg-secondary">Bot</span>' : '' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Privacy note -->
<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Catatan Privasi:</strong> Data analitik mencatat alamat IP dan informasi browser pengunjung untuk pemantauan trafik dan keamanan dasar.
    Data ini sebaiknya disimpan hanya selama diperlukan. Gunakan perintah CLI <code>php spark analytics:purge --days=90</code> untuk menghapus data lama secara berkala.
    Data tidak dikirim ke layanan eksternal.
</div>
<div class="alert alert-warning small">
    <i class="bi bi-geo-alt me-1"></i>
    <strong>Catatan Lokasi:</strong> Lokasi pengunjung bersifat <strong>perkiraan</strong> berdasarkan alamat IP dan database lokal (DB-IP Lite).
    Hasil bisa tidak akurat terutama saat pengunjung menggunakan VPN, jaringan seluler, proxy korporat, atau internet satelit.
    Lokasi tidak mewakili alamat rumah atau posisi GPS pengunjung. Data IP tidak dikirim ke layanan geolokasi eksternal.
</div>

<?php $this->endSection() ?>
