# Portal — Catatan Progres dan Handoff
Tanggal: 1 November 2026 (WIB)
Status: dijeda oleh pemilik untuk beristirahat.

## Konteks proyek
Portal adalah proyek website/CMS baru yang independen dari proyek personal-page / Bertahan Hidup dan LifeQuest. Pengembangan dilakukan melalui OpenCode di Mac pemilik. Catatan ini merangkum percakapan serta laporan OpenCode dan konfirmasi pemilik; bukan audit langsung repository.

## Lingkungan yang dilaporkan
- Folder proyek: /Volumes/Data/Project/portal
- PHP 8.5.12, CodeIgniter 4.7.5.
- Bootstrap 5.3.8 dari folder lokal bootstrap-5.
- Homebrew Apache; versi Apache tidak tercatat dalam sesi ini.
- MariaDB, database portal_db; versi MariaDB belum tercatat.
- URL lokal: http://127.0.0.1:8081/
- DocumentRoot: /Volumes/Data/Project/portal/public. URL tidak memerlukan /public.
- Referensi Include Apache usang ke personal-page/docs/apache/bertahanhidup.conf telah dibersihkan dan Portal memakai konfigurasi sendiri.
- GitHub: pemilik menyatakan hasil hingga implementasi About sudah dipush. SHA terbaru dan sinkronisasi remote belum diverifikasi di sesi ini.

## Dokumen acuan
- PRD_Portal_v1.0.md: PRD bahasa Inggris untuk OpenCode.
- docs/PAGE_STRUCTURE.md: pemetaan struktur halaman dan rancangan pengaturan CMS.
- PROGRESS.md: progres implementasi dan langkah penerimaan.
Isi terkini dokumen-dokumen ini harus dibaca di repository sebelum melanjutkan, jangan menimpa berdasarkan ringkasan ini.

## Keputusan desain
- Desain mengikuti page-template/index.html; folder page-template dipertahankan sebagai referensi asli.
- Aset runtime berada di public; desain dan interaksi template dijaga.
- Konten dan opsi layout terstruktur dikelola admin, bukan setiap div atau HTML/CSS bebas; tidak membuat page builder seperti Elementor.
- Komponen global dipisahkan secara konsep dari section homepage.
- Section tersembunyi harus menghilangkan menu anchor yang mengarah ke section itu.
- Sidebar artikel merupakan opsi usulan, bukan fitur homepage yang diasumsikan.
- Blog homepage nantinya mengambil artikel published dari modul artikel, bukan sumber konten duplikat.
- Kategori portfolio terpisah dari kategori artikel.
- Form kontak/email delivery belum diimplementasikan.
- Admin memakai label bahasa Indonesia. Sebagian pesan validasi identitas masih terlihat berbahasa Inggris.
- About saat ini memakai plain text yang di-escape dan baris baru menjadi br; rich text/sanitizer HTML penuh belum diimplementasikan.

## Selesai / dilaporkan berjalan
### 1. Homepage dan Bootstrap
- Homepage template terintegrasi melalui controller/view CodeIgniter dan route GET /.
- Error module is not defined pada all.js, imagesLoaded bukan fungsi, dan require is not defined pada jquery.video.js dilaporkan diperbaiki.
- Migrasi Bootstrap 4 ke Bootstrap 5.3.8 dilaporkan selesai tanpa memuat dua versi.
- Menu, filter portfolio, carousel, scrolling dan interaksi terkait dilaporkan berjalan.
- Laporan sebelumnya mencatat console 0 error/warning dan 0 aset gagal; belum merupakan verifikasi ulang kondisi terbaru.
- Commit historis: 5455e45 (stabilisasi homepage), d4a7f57 (migrasi Bootstrap).
- Google Fonts masih membutuhkan internet.

### 2. Fondasi CMS dan autentikasi
- Koneksi portal_db melalui .env yang diabaikan Git.
- Login /admin/login, dashboard /admin, POST /admin/logout, ganti password /admin/change-password.
- Password hashing, throttling login, regenerasi session, filter autentikasi dan CSRF dilaporkan dibuat.
- CLI pembuatan admin memakai input password tersembunyi; nama command belum tercatat di ringkasan, baca PROGRESS.md atau daftar command proyek.
- Error login 500 disebabkan AdminLoginAttemptModel mengaktifkan timestamp otomatis sementara tabel admin_login_attempts tidak punya updated_at; diperbaiki dengan menonaktifkan timestamp otomatis. Pastikan created_at tetap terisi jika meninjau implementasinya nanti.
- Uji OpenCode: anonim dialihkan ke login; login salah menampilkan pesan; login benar, logout, ganti password/relogin berhasil; token CSRF salah/hilang ditolak; homepage tetap HTTP 200.
- Commit fondasi: 912f7dc3e363ad5d98096753c2387e01ca880396.
- .env.example memakai placeholder; dump, log, cache, upload runtime tidak dimasukkan Git.
- Laporan pernah mencetak nilai yang tampaknya kredensial. Nilainya tidak disalin dalam catatan ini; jika aktif, perlu diganti. Status penggantian belum diketahui.

### 3. Identitas website, branding header dan hero/banner
- Admin:
  - /admin/settings/identity
  - /admin/settings/hero
- Tabel/model media, site_settings, homepage_sections dibuat melalui migrasi additive dan data awal berasal dari homepage.
- Identitas: judul/nama situs, metadata, logo, favicon dan apple touch icon.
- Hero: heading, deskripsi, rotating words, gambar latar dan tombol opsional.
- Upload/seleksi gambar dasar tersedia; file executable tersamar dilaporkan ditolak.
- Upload runtime: public/assets/portal/uploads/media/; diabaikan Git, perlu backup tersendiri.
- Dashboard diperbarui memakai data nyata dan modul belum tersedia dibuat nonaktif.
- Pemilik menyatakan tes identitas/hero sudah OK. Screenshot memperlihatkan validasi wajib bekerja dan homepage pada jendela sempit.
- Commit milestone identitas/hero dilaporkan dibuat; SHA tidak tercatat.

### 4. About
- Admin: /admin/settings/about.
- Migrasi 2026-11-01-154749_SeedAboutSection.php mengisi data awal About tanpa menimpa data tersimpan.
- Field: heading, body, image, alt text, label/link tombol, layout dan visibility.
- Layout: image_right, image_left, text_only.
- Memakai homepage_sections dan media selector/upload yang ada.
- Homepage dinamis pada #about; menyembunyikan section juga menyembunyikan menu About Us.
- OpenCode melaporkan tes penyimpanan/persistensi, field wajib, upload PNG valid, penolakan file tidak valid, visibility, ketiga layout, proteksi akses dan tampilan desktop lebar 1000 px.
- Mobile emulation dan interaksi sentuh belum diuji.
- Laporan awal menyebut uncommitted; setelah itu pemilik menyatakan hasil ini sudah dipush ke GitHub. Tidak perlu meminta commit ulang tanpa memeriksa Git.
- Penerimaan manual About secara rinci belum dikonfirmasi terpisah; push tidak otomatis berarti semua tes manual lulus.

## Masalah Git yang pernah muncul
VS Code meminta user.name dan user.email sebelum commit. Panduan yang diberikan:
git config user.name "Nama pemilik"
git config user.email "Email GitHub pemilik"
Konfigurasi lokal repository, lalu commit ulang tanpa stage ulang. Pemilik kemudian menyatakan sudah push.

## Belum selesai
- Services: prompt implementasi sudah diberikan, tetapi BELUM ada hasil pengerjaan atau konfirmasi selesai.
- Portfolio/gallery dan filter kategorinya.
- Testimonials.
- Artikel/blog, kategori, tag.
- Media library penuh: hapus, rename, metadata dan kebijakan referensi.
- Pengelolaan menu penuh.
- Contact, footer, kontrol urutan section sesuai rancangan.
- Pemrosesan form kontak dan pengiriman email.
- Verifikasi visual mobile menyeluruh.
- Backup database dan upload terpisah; push kode tidak mencakup isi database, .env atau upload runtime.

## Titik lanjut berikutnya
1. Tanyakan apakah prompt Services terakhir sudah dijalankan; jika ada hasil, review hasil itu dahulu. Jangan mengulang implementasi yang sudah ada.
2. Jika belum, lanjut implementasi Services saja sesuai docs/PAGE_STRUCTURE.md:
   - heading/deskripsi/visibility section;
   - tambah, edit, hapus dan urutkan kartu;
   - ikon dari daftar yang diizinkan, tanpa HTML bebas;
   - seed konten template tanpa menimpa data tersimpan;
   - form admin terlindungi, CSRF, validasi server, label/pesan Indonesia, escape output;
   - sembunyikan anchor ketika section tidak tampil;
   - jaga desain Bootstrap 5 dan section lain.
3. Verifikasi CRUD, urutan, persistensi, validasi, proteksi anonim serta rendering desktop/mobile jika alat tersedia.
4. Update PROGRESS.md dan serahkan untuk tes pemilik; prompt Services terakhir melarang commit/push otomatis.
5. Setelah diterima, commit/push mengikuti instruksi pemilik.

## Prompt Services terakhir
Read docs/PAGE_STRUCTURE.md and PROGRESS.md. Inspect existing code, then implement Services section management only.

Requirements:
- Reuse the existing section storage and admin layout.
- Provide section heading, description, and visibility settings.
- Support adding, editing, deleting, and ordering service cards using the fields mapped in PAGE_STRUCTURE.md.
- Use a predefined list of supported icons; do not accept arbitrary HTML.
- Initialize from current Services content without overwriting saved data.
- Protect all admin routes; use CSRF protection, server-side validation, Indonesian labels/messages, and safe output rendering.
- Preserve the current homepage design, Bootstrap 5, and existing interactions.
- When Services is hidden, also hide its navigation anchor.
- Verify CRUD, ordering, persistence, validation, anonymous access denial, and public rendering. Check desktop/mobile where browser tools permit; state untested items.
- Update PROGRESS.md with changes and manual acceptance steps.

Preserve existing settings and other sections. Use additive migrations if needed. Do not change Apache configuration, commit, or push.

Report the admin URL, changed files, verification results, and remaining limitations.

## Cara melanjutkan percakapan
Pemilik dapat mengatakan: "Zordone, lanjut Portal dari Services."
Utamakan progres aktual repository dan laporan terbaru, hindari mengganti-ganti prompt final tanpa kebutuhan baru.

