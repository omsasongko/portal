<?php
$name    = $name ?? 'image';
$current = $current ?? '';
$accept  = $accept ?? 'image/*';
$found   = false;
?>
<input type="hidden" name="current_<?= esc($name) ?>" value="<?= esc($current) ?>">
<?php if ($current): ?>
    <div class="mb-2">
        <img src="<?= base_url($current) ?>" alt="Current" style="max-height:80px;" class="img-thumbnail">
    </div>
<?php endif; ?>
<div class="mb-2">
    <input type="file" class="form-control" name="<?= esc($name) ?>" id="<?= esc($name) ?>" accept="<?= esc($accept) ?>">
    <div class="form-text">Unggah gambar baru untuk menggantikan yang dipilih. Maksimal 2 MB, format JPG/PNG/WebP/ICO.</div>
</div>
<?php if (! empty($media)): ?>
    <div class="row g-2">
        <?php foreach ($media as $m): ?>
            <?php
            $checked = ($current && $current === $m['filename']);
            if ($checked) {
                $found = true;
            }
            ?>
            <div class="col-3 col-md-2">
                <label class="d-block border rounded p-1 text-center <?= $checked ? 'border-primary bg-light' : '' ?>" style="cursor:pointer;">
                    <img src="<?= base_url($m['filename']) ?>" alt="" style="height:60px; object-fit:contain;" class="img-fluid">
                    <input type="radio" name="<?= esc($name) ?>" value="<?= esc($m['filename']) ?>" class="mt-1" <?= $checked ? 'checked' : '' ?>>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-muted small">Belum ada media. Unggah gambar di atas.</p>
<?php endif; ?>
