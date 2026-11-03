<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ubah Kata Sandi</h3>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('admin/change-password') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="old_password" class="form-label">Kata Sandi Lama</label>
                        <input type="password" class="form-control <?= isset($errors['old_password']) ? 'is-invalid' : '' ?>" id="old_password" name="old_password" required>
                        <?php if (isset($errors['old_password'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['old_password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Kata Sandi Baru</label>
                        <input type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" id="new_password" name="new_password" required minlength="8">
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['new_password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback"><?= esc($errors['confirm_password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="<?= base_url('admin') ?>" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
