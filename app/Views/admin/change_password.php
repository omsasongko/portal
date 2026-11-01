<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card admin-card">
            <div class="card-body p-4">
                <h4 class="card-title mb-4">Ubah Kata Sandi</h4>

                <form method="post" action="<?= base_url('admin/change-password') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="old_password" class="form-label">Kata Sandi Lama</label>
                        <input type="password" class="form-control <?= isset($errors['old_password']) ? 'is-invalid' : '' ?>" id="old_password" name="old_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Kata Sandi Baru</label>
                        <input type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" id="new_password" name="new_password" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" required>
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
