<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pengaturan Contact</h3>
    </div>
    <div class="card-body">
        <?php if (! empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('admin/settings/contact') ?>">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="heading" class="form-label">Judul Bagian <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['heading']) ? 'is-invalid' : '' ?>" id="heading" name="heading" value="<?= old('heading', $contact['heading'] ?? 'Contact') ?>" required maxlength="255">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="submit_label" class="form-label">Teks Tombol Kirim <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['submit_label']) ? 'is-invalid' : '' ?>" id="submit_label" name="submit_label" value="<?= old('submit_label', $contact['submit_label'] ?? 'Send Message') ?>" required maxlength="100">
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="4" maxlength="2000"><?= old('description', $contact['description'] ?? '') ?></textarea>
            </div>

            <h5 class="mt-4 mb-3">Label / Placeholder Formulir</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name_placeholder" class="form-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['name_placeholder']) ? 'is-invalid' : '' ?>" id="name_placeholder" name="name_placeholder" value="<?= old('name_placeholder', $contact['name_placeholder'] ?? 'Your Name') ?>" required maxlength="100">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email_placeholder" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['email_placeholder']) ? 'is-invalid' : '' ?>" id="email_placeholder" name="email_placeholder" value="<?= old('email_placeholder', $contact['email_placeholder'] ?? 'Your Email') ?>" required maxlength="100">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone_placeholder" class="form-label">Telepon <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['phone_placeholder']) ? 'is-invalid' : '' ?>" id="phone_placeholder" name="phone_placeholder" value="<?= old('phone_placeholder', $contact['phone_placeholder'] ?? 'Your Phone') ?>" required maxlength="100">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="message_placeholder" class="form-label">Pesan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['message_placeholder']) ? 'is-invalid' : '' ?>" id="message_placeholder" name="message_placeholder" value="<?= old('message_placeholder', $contact['message_placeholder'] ?? 'Your Message') ?>" required maxlength="100">
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?= old('is_enabled', $sectionEnabled) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_enabled">Tampilkan bagian Contact di homepage</label>
                </div>
                <div class="form-text">Jika dimatikan, bagian <code>#contact</code> disembunyikan.</div>
            </div>

            <div class="alert alert-light border">
                <h6 class="mb-1"><i class="bi bi-info-circle"></i> Pengaturan surel</h6>
                <p class="mb-0 small">Tujuan dan konfigurasi pengiriman surel formulir contact tidak diatur di sini. Perilaku pengiriman tetap mengikuti pengaturan server dan file <code>public/assets/portal/js/contact_me.js</code>.</p>
            </div>

            <div class="d-flex justify-content-between">
                <a href="<?= base_url('admin') ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
