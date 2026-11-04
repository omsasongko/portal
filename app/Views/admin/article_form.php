<?php $this->extend('admin/layout') ?>

<?php $this->section('content') ?>
<?php $errors = session()->getFlashdata('errors') ?? []; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title"><?= $article ? 'Edit Artikel' : 'Tambah Artikel' ?></h3>
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

        <form method="post" action="<?= base_url('admin/articles/save') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php if ($article): ?>
                <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Artikel <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" value="<?= old('title', $article['title'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= base_url('blog/') ?></span>
                            <input type="text" class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" id="slug" name="slug" value="<?= old('slug', $article['slug'] ?? '') ?>" placeholder="otomatis dari judul bila kosong">
                        </div>
                        <div class="form-text">Huruf kecil, angka, dan tanda hubung. Mengubah judul tidak otomatis mengubah slug yang sudah tersimpan.</div>
                    </div>

                    <div class="mb-3">
                        <label for="excerpt" class="form-label">Ringkasan (opsional)</label>
                        <textarea class="form-control <?= isset($errors['excerpt']) ? 'is-invalid' : '' ?>" id="excerpt" name="excerpt" rows="2" maxlength="500"><?= old('excerpt', $article['excerpt'] ?? '') ?></textarea>
                        <div class="form-text">Maksimal 500 karakter. Ditampilkan di kartu artikel pada homepage dan daftar Blog.</div>
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label">Isi Artikel</label>
                        <textarea class="form-control" id="body" name="body" rows="20"><?= old('body', $article['body'] ?? '') ?></textarea>
                        <div class="form-text">Konten disaring di server: hanya tag format standar (paragraf, judul, daftar, tautan, gambar, tabel) yang disimpan. Skrip dan URL berbahaya dihapus.</div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>" id="status" name="status">
                                    <option value="draft" <?= old('status', $article['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draf</option>
                                    <option value="published" <?= old('status', $article['status'] ?? '') === 'published' ? 'selected' : '' ?>>Terbit</option>
                                </select>
                                <div class="form-text">Draf hanya bisa dilihat admin lewat tombol Pratinjau.</div>
                            </div>

                            <div class="mb-3">
                                <label for="published_at" class="form-label">Tanggal Terbit</label>
                                <input type="date" class="form-control <?= isset($errors['published_at']) ? 'is-invalid' : '' ?>" id="published_at" name="published_at" value="<?= old('published_at', $article['published_at'] ?? date('Y-m-d')) ?>">
                                <div class="form-text">Kosongkan saat menerbitkan untuk memakai tanggal hari ini.</div>
                            </div>

                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategori</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">— Tanpa kategori —</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= (int) $c['id'] ?>" <?= (int) old('category_id', $article['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Kelola kategori lewat menu <a href="<?= base_url('admin/categories') ?>">Kategori</a>.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tag</label>
                                <div class="border rounded p-2" style="max-height:180px; overflow-y:auto;">
                                    <?php if (empty($tags)): ?>
                                        <p class="text-muted small mb-0">Belum ada tag. Kelola lewat menu <a href="<?= base_url('admin/tags') ?>">Tag</a>.</p>
                                    <?php else: ?>
                                        <?php foreach ($tags as $t): ?>
                                            <?php $checked = in_array((int) $t['id'], array_map('intval', old('tags', $tagIds)), true); ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="tags[]" value="<?= (int) $t['id'] ?>" id="tag-<?= (int) $t['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="tag-<?= (int) $t['id'] ?>"><?= esc($t['name']) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tata Letak Halaman Detail <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="detail_layout" id="layout-sidebar" value="sidebar" <?= old('detail_layout', $article['detail_layout'] ?? 'sidebar') === 'sidebar' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="layout-sidebar">Area baca + sidebar artikel terkait</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="detail_layout" id="layout-full" value="full" <?= old('detail_layout', $article['detail_layout'] ?? '') === 'full' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="layout-full">Area baca lebar penuh</label>
                                </div>
                                <div class="form-text">Template responsif bawaan. Pengaturan ini hanya memengaruhi halaman detail artikel ini — bukan urutan menu, urutan bagian homepage, atau tata letak kartu Blog.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Gambar Sampul</h3>
                        </div>
                        <div class="card-body">
                            <?php if (isset($errors['cover_image'])): ?>
                                <div class="alert alert-danger py-2"><?= esc($errors['cover_image']) ?></div>
                            <?php endif; ?>
                            <?= view('admin/_media_selector', ['name' => 'cover_image', 'current' => old('current_cover_image', $article['cover_image'] ?? ''), 'media' => $media]) ?>
                            <div class="mb-3 mt-2">
                                <label for="cover_alt" class="form-label">Teks Alternatif (alt)</label>
                                <input type="text" class="form-control <?= isset($errors['cover_alt']) ? 'is-invalid' : '' ?>" id="cover_alt" name="cover_alt" value="<?= old('cover_alt', $article['cover_alt'] ?? '') ?>" maxlength="255">
                                <div class="form-text">Deskripsi singkat gambar untuk aksesibilitas.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= $article ? 'Simpan Perubahan' : 'Tambah Artikel' ?></button>
                <?php if ($article): ?>
                    <a href="<?= base_url('admin/articles/preview/' . (int) $article['id']) ?>" class="btn btn-info" target="_blank">Pratinjau</a>
                <?php endif; ?>
                <a href="<?= base_url('admin/articles') ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= base_url('assets/admin/vendor/tinymce/tinymce.min.js') ?>" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from title (only until the slug is manually edited)
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    const isEdit = <?= $article ? 'true' : 'false' ?>;
    let slugManuallyEdited = isEdit && slugInput.value.trim() !== '';

    slugInput.addEventListener('input', function() {
        slugManuallyEdited = true;
    });

    titleInput.addEventListener('input', function() {
        if (!slugManuallyEdited) {
            slugInput.value = titleInput.value
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
        }
    });

    // CSRF token for AJAX requests
    const csrfName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';
    const baseUrl = '<?= base_url() ?>';

    function refreshCsrf(response) {
        // With regenerate=true, read the new CSRF token from cookie
        var match = document.cookie.match(/(?:^|;\s*)csrf_cookie_name=([^;]+)/);
        if (match) {
            csrfHash = decodeURIComponent(match[1]);
        }
        if (response && response[csrfName]) {
            csrfHash = response[csrfName];
        }
    }

    // Initialize TinyMCE with full image management + YouTube
    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
    tinymce.init({
        selector: '#body',
        height: 480,
        language: 'id',
        menubar: 'edit insert format table',
        plugins: 'lists link image table code wordcount',
        toolbar: [
            'undo redo | blocks | bold italic underline strikethrough',
            'alignleft aligncenter alignright | bullist numlist | link | table | code',
            'uploadimage medialibrary | imgalignleft imgaligncenter imgalignright | imgresize25 imgresize50 imgresize75 imgresize100 | imgcrop | youtube'
        ],
        valid_elements: 'p[class],br,strong,b,em,i,u,s,sub,sup,h1,h2,h3,h4,h5,h6,ul,ol,li,a[href|title|target|rel],img[src|alt|title|width|height|style|class],blockquote,pre,code,table[class],thead,tbody,tfoot,tr,th[colspan|rowspan],td[colspan|rowspan],hr,div[class|style],span[class],iframe[src|width|height|frameborder|allow|allowfullscreen|title|class|style]',
        convert_urls: false,
        skin: currentTheme === 'dark' ? 'oxide-dark' : 'oxide',
        content_css: currentTheme === 'dark' ? 'dark' : 'default',
        promotion: false,
        branding: false,
        license_key: 'gpl',
        content_style: `
            img.img-align-left { display: block; margin: 10px auto 10px 0; }
            img.img-align-center { display: block; margin: 10px auto; }
            img.img-align-right { display: block; margin: 10px 0 10px auto; }
            img { max-width: 100%; height: auto; cursor: pointer; }
            .video-embed-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; margin: 10px 0; }
            .video-embed-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
            .mce-img-controls { position: absolute; background: rgba(0,0,0,0.75); color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; z-index: 10; display: flex; gap: 4px; }
            .mce-img-controls button { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,0.5); border-radius: 3px; padding: 2px 8px; cursor: pointer; font-size: 11px; }
            .mce-img-controls button:hover { background: rgba(255,255,255,0.2); }
        `,

        // ----- Custom toolbar button: Unggah Gambar -----
        setup: function(editor) {
            editor.ui.registry.addButton('uploadimage', {
                text: 'Unggah Gambar',
                tooltip: 'Unggah gambar dari komputer',
                onAction: function() {
                    var input = document.createElement('input');
                    input.type = 'file';
                    input.accept = '.jpg,.jpeg,.png,.gif,.webp';
                    input.onchange = function() {
                        var file = this.files[0];
                        if (!file) return;

                        var ext = file.name.split('.').pop().toLowerCase();
                        if (!['jpg','jpeg','png','gif','webp'].includes(ext)) {
                            editor.notificationManager.open({ text: 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.', type: 'error' });
                            return;
                        }
                        if (file.size < 51200) {
                            editor.notificationManager.open({ text: 'Ukuran file terlalu kecil. Minimum 50 KB.', type: 'error' });
                            return;
                        }
                        if (file.size > 10485760) {
                            editor.notificationManager.open({ text: 'Ukuran file terlalu besar. Maksimum 10 MB.', type: 'error' });
                            return;
                        }

                        var formData = new FormData();
                        formData.append('file', file);
                        formData.append(csrfName, csrfHash);

                        var notification = editor.notificationManager.open({ text: 'Mengunggah gambar...', type: 'info', closeButton: false });

                        fetch(baseUrl + 'admin/articles/upload-image', {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                        .then(function(r) { return r.json().then(function(d) { return {ok: r.ok, data: d}; }); })
                        .then(function(result) {
                            notification.close();
                            refreshCsrf(result.data);
                            if (result.ok && result.data.location) {
                                editor.insertContent('<img src="' + result.data.location + '" alt="" class="img-fluid" style="width: 100%; max-width: 100%;" />');
                                editor.notificationManager.open({ text: 'Gambar berhasil diunggah.', type: 'success', timeout: 3000 });
                            } else {
                                editor.notificationManager.open({ text: result.data.error || 'Gagal mengunggah gambar.', type: 'error' });
                            }
                        })
                        .catch(function(err) {
                            notification.close();
                            editor.notificationManager.open({ text: 'Gagal mengunggah gambar. Silakan coba lagi.', type: 'error' });
                        });
                    };
                    input.click();
                }
            });

            // ----- Custom toolbar button: Pilih dari Pustaka -----
            editor.ui.registry.addButton('medialibrary', {
                text: 'Pilih dari Pustaka',
                tooltip: 'Pilih gambar dari pustaka media',
                onAction: function() {
                    fetch(baseUrl + 'admin/articles/media-library', {
                        method: 'GET',
                        credentials: 'same-origin'
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(items) {
                        if (!items || items.length === 0) {
                            editor.notificationManager.open({ text: 'Pustaka media kosong. Unggah gambar terlebih dahulu.', type: 'warning' });
                            return;
                        }

                        var html = '<div style="max-height:400px;overflow-y:auto;display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:8px;">';
                        items.forEach(function(item) {
                            html += '<div style="cursor:pointer;border:2px solid transparent;border-radius:4px;overflow:hidden;" '
                                + 'class="media-lib-item" data-url="' + item.url + '">'
                                + '<img src="' + item.url + '" style="width:100%;height:80px;object-fit:cover;" alt="' + (item.title || '') + '" />'
                                + '<div style="font-size:11px;padding:2px 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + (item.title || '') + '</div>'
                                + '</div>';
                        });
                        html += '</div>';

                        editor.windowManager.open({
                            title: 'Pilih dari Pustaka Media',
                            size: 'large',
                            body: {
                                type: 'panel',
                                items: [{ type: 'htmlpanel', html: html }]
                            },
                            buttons: [{ type: 'cancel', text: 'Batal' }],
                            onAction: function(api, details) {}
                        });

                        // After dialog opens, attach click handlers
                        setTimeout(function() {
                            var dialogEl = document.querySelector('.tox-dialog');
                            if (dialogEl) {
                                dialogEl.querySelectorAll('.media-lib-item').forEach(function(el) {
                                    el.addEventListener('click', function() {
                                        var url = this.getAttribute('data-url');
                                        editor.insertContent('<img src="' + url + '" alt="" class="img-fluid" style="width: 100%; max-width: 100%;" />');
                                        editor.windowManager.close();
                                        editor.notificationManager.open({ text: 'Gambar berhasil ditambahkan.', type: 'success', timeout: 3000 });
                                    });
                                });
                            }
                        }, 200);
                    })
                    .catch(function() {
                        editor.notificationManager.open({ text: 'Gagal memuat pustaka media.', type: 'error' });
                    });
                }
            });

            // ----- Alignment buttons -----
            function setImgAlign(align) {
                var node = editor.selection.getNode();
                if (node.nodeName !== 'IMG') {
                    editor.notificationManager.open({ text: 'Pilih gambar terlebih dahulu.', type: 'warning', timeout: 2000 });
                    return;
                }
                node.classList.remove('img-align-left', 'img-align-center', 'img-align-right');
                node.classList.add('img-align-' + align);
                editor.nodeChanged();
            }

            editor.ui.registry.addButton('imgalignleft', {
                icon: 'align-left',
                tooltip: 'Rata kiri',
                onAction: function() { setImgAlign('left'); }
            });
            editor.ui.registry.addButton('imgaligncenter', {
                icon: 'align-center',
                tooltip: 'Rata tengah',
                onAction: function() { setImgAlign('center'); }
            });
            editor.ui.registry.addButton('imgalignright', {
                icon: 'align-right',
                tooltip: 'Rata kanan',
                onAction: function() { setImgAlign('right'); }
            });

            // ----- Resize preset buttons -----
            function setImgResize(pct) {
                var node = editor.selection.getNode();
                if (node.nodeName !== 'IMG') {
                    editor.notificationManager.open({ text: 'Pilih gambar terlebih dahulu.', type: 'warning', timeout: 2000 });
                    return;
                }
                node.style.width = pct + '%';
                node.style.maxWidth = '100%';
                node.style.height = 'auto';
                node.removeAttribute('width');
                node.removeAttribute('height');
                editor.nodeChanged();
            }

            editor.ui.registry.addButton('imgresize25', {
                text: '25%',
                tooltip: 'Ukuran 25% lebar konten',
                onAction: function() { setImgResize(25); }
            });
            editor.ui.registry.addButton('imgresize50', {
                text: '50%',
                tooltip: 'Ukuran 50% lebar konten',
                onAction: function() { setImgResize(50); }
            });
            editor.ui.registry.addButton('imgresize75', {
                text: '75%',
                tooltip: 'Ukuran 75% lebar konten',
                onAction: function() { setImgResize(75); }
            });
            editor.ui.registry.addButton('imgresize100', {
                text: '100%',
                tooltip: 'Ukuran 100% lebar konten',
                onAction: function() { setImgResize(100); }
            });

            // ----- Crop button -----
            editor.ui.registry.addButton('imgcrop', {
                text: 'Potong',
                tooltip: 'Potong gambar yang dipilih',
                onAction: function() {
                    var node = editor.selection.getNode();
                    if (node.nodeName !== 'IMG') {
                        editor.notificationManager.open({ text: 'Pilih gambar terlebih dahulu.', type: 'warning', timeout: 2000 });
                        return;
                    }
                    var imgSrc = node.getAttribute('src');

                    // Build crop dialog with canvas
                    var cropDialog = document.createElement('div');
                    cropDialog.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:99999;display:flex;align-items:center;justify-content:center;';
                    cropDialog.innerHTML = '<div style="background:#fff;border-radius:8px;padding:20px;max-width:800px;width:90%;max-height:90vh;overflow-y:auto;">'
                        + '<h4 style="margin:0 0 10px;">Potong Gambar</h4>'
                        + '<div id="crop-container" style="position:relative;display:inline-block;max-width:100%;margin-bottom:10px;user-select:none;">'
                        + '<img id="crop-img" src="' + imgSrc + '" style="max-width:100%;display:block;" crossorigin="anonymous" />'
                        + '<div id="crop-selection" style="position:absolute;border:2px dashed #0d6efd;background:rgba(13,110,253,0.15);cursor:move;display:none;"></div>'
                        + '</div>'
                        + '<div style="margin-bottom:10px;font-size:13px;color:#666;" id="crop-info">Klik dan seret pada gambar untuk memilih area pemotongan.</div>'
                        + '<div style="display:flex;gap:8px;justify-content:flex-end;">'
                        + '<button id="crop-cancel" style="padding:8px 16px;border:1px solid #ccc;border-radius:4px;background:#fff;cursor:pointer;">Batal</button>'
                        + '<button id="crop-apply" style="padding:8px 16px;border:none;border-radius:4px;background:#0d6efd;color:#fff;cursor:pointer;" disabled>Terapkan</button>'
                        + '</div></div>';
                    document.body.appendChild(cropDialog);

                    var cropImg = document.getElementById('crop-img');
                    var cropSel = document.getElementById('crop-selection');
                    var cropApply = document.getElementById('crop-apply');
                    var cropCancel = document.getElementById('crop-cancel');
                    var cropInfo = document.getElementById('crop-info');
                    var cropContainer = document.getElementById('crop-container');

                    var startX, startY, isSelecting = false;
                    var selX, selY, selW, selH;
                    var scaleX, scaleY;

                    cropImg.onload = function() {
                        scaleX = cropImg.naturalWidth / cropImg.clientWidth;
                        scaleY = cropImg.naturalHeight / cropImg.clientHeight;
                    };
                    if (cropImg.complete) {
                        scaleX = cropImg.naturalWidth / cropImg.clientWidth;
                        scaleY = cropImg.naturalHeight / cropImg.clientHeight;
                    }

                    cropContainer.addEventListener('mousedown', function(e) {
                        if (e.target === cropSel) return; // dragging
                        var rect = cropImg.getBoundingClientRect();
                        startX = e.clientX - rect.left;
                        startY = e.clientY - rect.top;
                        isSelecting = true;
                        cropSel.style.display = 'block';
                        cropSel.style.left = startX + 'px';
                        cropSel.style.top = startY + 'px';
                        cropSel.style.width = '0px';
                        cropSel.style.height = '0px';
                        e.preventDefault();
                    });

                    document.addEventListener('mousemove', function onMove(e) {
                        if (!isSelecting) return;
                        var rect = cropImg.getBoundingClientRect();
                        var curX = Math.max(0, Math.min(e.clientX - rect.left, cropImg.clientWidth));
                        var curY = Math.max(0, Math.min(e.clientY - rect.top, cropImg.clientHeight));
                        selX = Math.min(startX, curX);
                        selY = Math.min(startY, curY);
                        selW = Math.abs(curX - startX);
                        selH = Math.abs(curY - startY);
                        cropSel.style.left = selX + 'px';
                        cropSel.style.top = selY + 'px';
                        cropSel.style.width = selW + 'px';
                        cropSel.style.height = selH + 'px';
                        cropApply.disabled = (selW < 10 || selH < 10);
                        cropInfo.textContent = 'Area: ' + Math.round(selX * scaleX) + ',' + Math.round(selY * scaleY) + ' – ' + Math.round(selW * scaleX) + '×' + Math.round(selH * scaleY) + ' px';
                        cropDialog._onMove = onMove;
                    });

                    document.addEventListener('mouseup', function onUp() {
                        isSelecting = false;
                        cropDialog._onUp = onUp;
                    });

                    cropCancel.addEventListener('click', function() {
                        if (cropDialog._onMove) document.removeEventListener('mousemove', cropDialog._onMove);
                        if (cropDialog._onUp) document.removeEventListener('mouseup', cropDialog._onUp);
                        document.body.removeChild(cropDialog);
                    });

                    cropApply.addEventListener('click', function() {
                        cropApply.disabled = true;
                        cropApply.textContent = 'Memproses...';

                        // Convert displayed src to relative path
                        var relSrc = imgSrc;
                        if (relSrc.indexOf(baseUrl) === 0) {
                            relSrc = relSrc.substring(baseUrl.length);
                        }

                        var payload = {
                            src: relSrc,
                            x: Math.round(selX * scaleX),
                            y: Math.round(selY * scaleY),
                            width: Math.round(selW * scaleX),
                            height: Math.round(selH * scaleY)
                        };

                        fetch(baseUrl + 'admin/articles/crop-image', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfHash },
                            body: JSON.stringify(payload),
                            credentials: 'same-origin'
                        })
                        .then(function(r) { return r.json().then(function(d) { return {ok: r.ok, data: d}; }); })
                        .then(function(result) {
                            refreshCsrf(result.data);
                            if (result.ok && result.data.location) {
                                node.setAttribute('src', result.data.location);
                                editor.nodeChanged();
                                editor.notificationManager.open({ text: 'Gambar berhasil dipotong.', type: 'success', timeout: 3000 });
                            } else {
                                editor.notificationManager.open({ text: result.data.error || 'Gagal memotong gambar.', type: 'error' });
                            }
                            if (cropDialog._onMove) document.removeEventListener('mousemove', cropDialog._onMove);
                            if (cropDialog._onUp) document.removeEventListener('mouseup', cropDialog._onUp);
                            document.body.removeChild(cropDialog);
                        })
                        .catch(function() {
                            editor.notificationManager.open({ text: 'Gagal memotong gambar. Silakan coba lagi.', type: 'error' });
                            cropApply.disabled = false;
                            cropApply.textContent = 'Terapkan';
                        });
                    });
                }
            });

            // ----- YouTube embed button -----
            editor.ui.registry.addButton('youtube', {
                text: 'YouTube',
                tooltip: 'Sematkan YouTube',
                onAction: function() {
                    editor.windowManager.open({
                        title: 'Sematkan Video YouTube',
                        body: {
                            type: 'panel',
                            items: [{
                                type: 'input',
                                name: 'youtubeUrl',
                                label: 'URL Video YouTube',
                                placeholder: 'https://www.youtube.com/watch?v=...'
                            }, {
                                type: 'htmlpanel',
                                html: '<p style="font-size:12px;color:#666;margin-top:4px;">Format yang didukung: youtube.com/watch, youtu.be, youtube.com/shorts.<br>Pemutaran memerlukan koneksi internet dan video yang mengizinkan penyematan.</p>'
                            }]
                        },
                        buttons: [
                            { type: 'cancel', text: 'Batal' },
                            { type: 'submit', text: 'Sematkan', primary: true }
                        ],
                        onSubmit: function(api) {
                            var data = api.getData();
                            var url = (data.youtubeUrl || '').trim();
                            if (!url) {
                                editor.notificationManager.open({ text: 'Masukkan URL YouTube.', type: 'warning' });
                                return;
                            }

                            var payload = { url: url };

                            fetch(baseUrl + 'admin/articles/embed-youtube', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfHash },
                                body: JSON.stringify(payload),
                                credentials: 'same-origin'
                            })
                            .then(function(r) { return r.json().then(function(d) { return {ok: r.ok, data: d}; }); })
                            .then(function(result) {
                                refreshCsrf(result.data);
                                if (result.ok && result.data.html) {
                                    editor.insertContent(result.data.html + '<p>&nbsp;</p>');
                                    api.close();
                                    editor.notificationManager.open({ text: 'Video YouTube berhasil disematkan.', type: 'success', timeout: 3000 });
                                } else {
                                    editor.notificationManager.open({ text: result.data.error || 'URL YouTube tidak valid.', type: 'error' });
                                }
                            })
                            .catch(function() {
                                editor.notificationManager.open({ text: 'Gagal menyematkan video. Silakan coba lagi.', type: 'error' });
                            });
                        }
                    });
                }
            });

            // ----- Image click handler: edit alt / remove -----
            editor.on('click', function(e) {
                if (e.target.nodeName === 'IMG') {
                    editor.selection.select(e.target);
                }
            });

            editor.on('dblclick', function(e) {
                if (e.target.nodeName === 'IMG') {
                    var imgNode = e.target;
                    editor.windowManager.open({
                        title: 'Edit Gambar',
                        body: {
                            type: 'panel',
                            items: [
                                { type: 'input', name: 'altText', label: 'Teks Alternatif (alt)', placeholder: 'Deskripsi gambar' },
                                { type: 'htmlpanel', html: '<p style="font-size:12px;color:#666;">Deskripsi gambar untuk aksesibilitas dan SEO.</p>' }
                            ]
                        },
                        initialData: { altText: imgNode.getAttribute('alt') || '' },
                        buttons: [
                            { type: 'cancel', text: 'Batal' },
                            { type: 'custom', text: 'Hapus Gambar', name: 'removeImg' },
                            { type: 'submit', text: 'Simpan', primary: true }
                        ],
                        onSubmit: function(api) {
                            imgNode.setAttribute('alt', api.getData().altText);
                            editor.nodeChanged();
                            api.close();
                        },
                        onAction: function(api, details) {
                            if (details.name === 'removeImg') {
                                editor.dom.remove(imgNode);
                                editor.nodeChanged();
                                api.close();
                                editor.notificationManager.open({ text: 'Gambar dihapus dari artikel.', type: 'info', timeout: 2000 });
                            }
                        }
                    });
                }
            });
        }
    });
});
</script>
<?php $this->endSection() ?>
