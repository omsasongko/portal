<?php $this->extend('admin/auth_layout') ?>

<?php $this->section('content') ?>
<p class="login-box-msg">Masuk ke panel admin</p>

<form method="post" action="<?= base_url('admin/login') ?>">
    <?= csrf_field() ?>
    <div class="input-group mb-3">
        <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" id="username" name="username" value="<?= old('username') ?>" placeholder="Username" required>
        <div class="input-group-text"><span class="bi bi-person-fill"></span></div>
        <?php if (isset($errors['username'])): ?>
            <div class="invalid-feedback"><?= esc($errors['username']) ?></div>
        <?php endif; ?>
    </div>
    <div class="input-group mb-3">
        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" placeholder="Kata Sandi" required>
        <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback"><?= esc($errors['password']) ?></div>
        <?php endif; ?>
    </div>
    <div class="row">
        <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block w-100">Masuk</button>
        </div>
    </div>
</form>
<?php $this->endSection() ?>
