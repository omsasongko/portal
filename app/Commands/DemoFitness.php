<?php

namespace App\Commands;

use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\MediaModel;
use App\Models\TagModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Membuat konten demo bertema fitness (3 artikel berbahasa Indonesia,
 * kategori "Fitness", tag terkait, dan foto sampul lokal).
 *
 * Aman dijalankan berulang kali: artikel/kategori/tag/media yang sudah
 * ada (dicocokkan lewat slug atau nama file) tidak akan diduplikasi
 * maupun ditimpa.
 */
class DemoFitness extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:fitness';
    protected $description = 'Membuat konten demo fitness (3 artikel, kategori, tag, foto sampul). Idempoten — aman diulang.';
    protected $usage       = 'demo:fitness';

    /**
     * Foto sampul: sumber asli di uploads/demo (lihat README.md di folder itu
     * untuk lisensi), disalin ke uploads/media dan didaftarkan di tabel media.
     */
    private array $photos = [
        [
            'file'  => 'demo-fitness-gym-area.jpg',
            'title' => 'Demo Fitness: Area peralatan di pusat kebugaran',
            'source' => 'https://unsplash.com/photos/1534438327276-14e5300c3a48',
        ],
        [
            'file'  => 'demo-fitness-journal.jpg',
            'title' => 'Demo Fitness: Menulis jurnal aktivitas',
            'source' => 'https://unsplash.com/photos/1455390582262-044cdead277a',
        ],
        [
            'file'  => 'demo-fitness-gym-bag.jpg',
            'title' => 'Demo Fitness: Tas perlengkapan latihan',
            'source' => 'https://unsplash.com/photos/1553062407-98eeb64c6a62',
        ],
    ];

    public function run(array $params)
    {
        $categoryModel = new CategoryModel();
        $tagModel      = new TagModel();
        $mediaModel    = new MediaModel();
        $articleModel  = new ArticleModel();

        CLI::write('=== Demo Konten Fitness ===', 'yellow');
        CLI::write('Foto: Unsplash License (bebas komersial/non-komersial, tanpa atribusi wajib).');
        CLI::write('Sumber & lisensi lengkap: public/assets/portal/uploads/demo/README.md');
        CLI::newLine();

        // 1. Kategori "Fitness" (jangan duplikasi).
        $category = $categoryModel->where('slug', 'fitness')->first();
        if ($category) {
            CLI::write('- Kategori "Fitness" sudah ada (id ' . $category['id'] . '), dipakai ulang.', 'dark_gray');
        } else {
            $categoryModel->insert([
                'name'        => 'Fitness',
                'slug'        => 'fitness',
                'description' => 'Kategori demo: catatan dan panduan ringan seputar pusat kebugaran.',
            ]);
            $category = $categoryModel->where('slug', 'fitness')->first();
            CLI::write('+ Kategori "Fitness" dibuat (id ' . $category['id'] . ').', 'green');
        }
        $categoryId = (int) $category['id'];

        // 2. Tag terkait.
        $tagDefs = [
            ['name' => 'Gym',                 'slug' => 'gym'],
            ['name' => 'Jurnal Latihan',      'slug' => 'jurnal-latihan'],
            ['name' => 'Perlengkapan Latihan', 'slug' => 'perlengkapan-latihan'],
        ];
        $tagIds = [];
        foreach ($tagDefs as $def) {
            $tag = $tagModel->where('slug', $def['slug'])->first();
            if ($tag) {
                CLI::write('- Tag "' . $def['name'] . '" sudah ada, dipakai ulang.', 'dark_gray');
            } else {
                $tagModel->insert(['name' => $def['name'], 'slug' => $def['slug'], 'description' => null]);
                $tag = $tagModel->where('slug', $def['slug'])->first();
                CLI::write('+ Tag "' . $def['name'] . '" dibuat.', 'green');
            }
            $tagIds[$def['slug']] = (int) $tag['id'];
        }

        // 3. Foto sampul: salin ke media + daftarkan (idempoten per nama file).
        $mediaPaths = [];
        $sourceDir = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'portal' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR;
        $targetDir = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'portal' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR;

        foreach ($this->photos as $photo) {
            $relative = 'assets/portal/uploads/media/' . $photo['file'];
            $existing = $mediaModel->where('filename', $relative)->first();

            if ($existing) {
                CLI::write('- Media "' . $photo['file'] . '" sudah terdaftar, dipakai ulang.', 'dark_gray');
            } else {
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                if (! is_file($sourceDir . $photo['file'])) {
                    CLI::error('Foto sumber tidak ditemukan: ' . $sourceDir . $photo['file'] . ' — artikel tetap dibuat tanpa sampul.');
                    $mediaPaths[$photo['file']] = null;
                    continue;
                }
                if (! is_file($targetDir . $photo['file'])) {
                    copy($sourceDir . $photo['file'], $targetDir . $photo['file']);
                }
                $mediaModel->insert([
                    'title'     => $photo['title'],
                    'filename'  => $relative,
                    'mime_type' => 'image/jpeg',
                ]);
                CLI::write('+ Media "' . $photo['file'] . '" disalin & didaftarkan.', 'green');
            }
            $mediaPaths[$photo['file']] = $relative;
        }

        // 4. Tiga artikel demo (dicocokkan lewat slug — tidak menimpa yang sudah ada).
        $demoNote = '<p><em>[Konten demo — dibuat oleh perintah <code>demo:fitness</code>. Silakan ubah atau hapus sesuai kebutuhan.]</em></p>';

        $articles = [
            [
                'slug'          => 'mengenal-area-dan-peralatan-di-pusat-kebugaran',
                'title'         => 'Mengenal Area dan Peralatan di Pusat Kebugaran',
                'excerpt'       => '[Demo] Panduan ringan mengenal zona-zona umum di gym — area kardio, beban bebas, dan mesin — beserta etika dasarnya.',
                'status'        => 'published',
                'detail_layout' => 'sidebar',
                'days_ago'      => 3,
                'cover'         => 'demo-fitness-gym-area.jpg',
                'cover_alt'     => 'Deretan peralatan beban di area latihan sebuah pusat kebugaran',
                'tags'          => ['gym'],
                'body'          => $demoNote
                    . '<p>Bagi pengunjung baru, pusat kebugaran bisa terasa seperti ruangan penuh mesin asing. Padahal sebagian besar gym modern menata areanya dengan pola yang mirip. Mengenali zona-zona ini membuat sesi latihan terasa lebih terarah dan nyaman.</p>'
                    . '<h2>Area Kardio</h2>'
                    . '<p>Biasanya terletak di bagian depan atau dekat jendela. Isinya treadmill, sepeda statis, elliptical, dan rowing machine. Area ini cocok untuk pemanasan sebelum lanjut ke beban, atau untuk sesi tersendiri di hari tertentu.</p>'
                    . '<h2>Area Beban Bebas</h2>'
                    . '<p>Rak dumbbell, barbell, kettlebell, dan bangku datar menjadi pusat area ini. Beberapa etika dasar yang berlaku umum:</p>'
                    . '<ul>'
                    . '<li>Kembalikan beban ke rak setelah dipakai.</li>'
                    . '<li>Lap keringat di bangku atau matras yang digunakan.</li>'
                    . '<li>Bergantian memakai bangku saat ruangan sedang ramai.</li>'
                    . '</ul>'
                    . '<h2>Area Mesin dan Fungsional</h2>'
                    . '<p>Deretan mesin dengan jalur gerak terpandu membantu pemula melatih kelompok otot tertentu dengan lebih terarah. Di banyak gym, ada pula zona fungsional berisi matras, bola, resistance band, dan rig untuk latihan berbasis gerakan.</p>'
                    . '<blockquote>Tips: datang di jam sepi pada kunjungan pertama memberi waktu untuk berkeliling dan mencoba beberapa mesin tanpa terburu-buru.</blockquote>'
                    . '<p>Dengan mengenali tata letak ini, Anda bisa menyusun rute latihan sendiri — misalnya mulai dari kardio ringan, lanjut ke beban bebas, lalu menutup sesi di area peregangan.</p>',
            ],
            [
                'slug'          => 'catatan-perjalanan-fitness-menyusun-jurnal-aktivitas',
                'title'         => 'Catatan Perjalanan Fitness: Menyusun Jurnal Aktivitas',
                'excerpt'       => '[Demo] Cara sederhana menyusun jurnal latihan: apa yang dicatat, format yang praktis, dan kebiasaan kecil agar jurnal tetap terisi.',
                'status'        => 'published',
                'detail_layout' => 'full',
                'days_ago'      => 2,
                'cover'         => 'demo-fitness-journal.jpg',
                'cover_alt'     => 'Buku catatan dan pena di atas meja untuk menulis jurnal aktivitas',
                'tags'          => ['jurnal-latihan'],
                'body'          => $demoNote
                    . '<p>Banyak orang berlatih rutin tetapi sulit mengingat apa yang sudah dilakukan minggu lalu. Jurnal aktivitas membantu: catatan singkat yang konsisten sering kali lebih berguna daripada rencana rumit yang tidak pernah ditulis.</p>'
                    . '<h2>Apa Saja yang Perlu Dicatat</h2>'
                    . '<p>Tidak perlu format baku. Empat hal ini sudah cukup untuk memulai:</p>'
                    . '<ol>'
                    . '<li><strong>Tanggal dan durasi</strong> — kapan dan berapa lama sesi berlangsung.</li>'
                    . '<li><strong>Jenis latihan</strong> — misalnya kardio, beban atas, atau kelas bersama.</li>'
                    . '<li><strong>Beban dan pengulangan</strong> — angka kasar sudah cukup, misal "dumbbell 8 kg, 3 set".</li>'
                    . '<li><strong>Catatan rasa</strong> — satu kalimat tentang bagaimana sesi terasa: ringan, menantang, atau perlu penyesuaian.</li>'
                    . '</ol>'
                    . '<h2>Pilih Format yang Paling Sering Dipakai</h2>'
                    . '<p>Buku catatan kecil, spreadsheet, atau aplikasi catatan di ponsel — semuanya sah. Format terbaik adalah format yang benar-benar Anda isi setelah latihan, bukan yang paling rapi di atas kertas.</p>'
                    . '<h3>Contoh Entri Singkat</h3>'
                    . '<blockquote>Selasa, 45 menit — treadmill 20 menit, beban atas (dumbbell press 3x10, row 3x8), peregangan. Terasa lebih ringan dari minggu lalu.</blockquote>'
                    . '<h2>Menjaga Kebiasaan Tetap Hidup</h2>'
                    . '<ul>'
                    . '<li>Tulis segera setelah sesi, selagi ingatan masih segar.</li>'
                    . '<li>Izinkan entri berantakan — satu baris tetap lebih baik daripada halaman kosong.</li>'
                    . '<li>Tinjau sebulan sekali untuk melihat pola: latihan mana yang konsisten, mana yang sering terlewat.</li>'
                    . '</ul>'
                    . '<p>Jurnal bukan alat penilaian diri, melainkan peta perjalanan. Dari catatan sederhana, penyesuaian kecil biasanya muncul dengan sendirinya.</p>',
            ],
            [
                'slug'          => 'isi-tas-gym-perlengkapan-untuk-sesi-latihan',
                'title'         => 'Isi Tas Gym: Perlengkapan untuk Sesi Latihan',
                'excerpt'       => '[Demo] Daftar perlengkapan praktis untuk tas gym — dari botol minum sampai perlengkapan kecil yang sering terlupakan.',
                'status'        => 'draft',
                'days_ago'      => 1,
                'detail_layout' => 'sidebar',
                'cover'         => 'demo-fitness-gym-bag.jpg',
                'cover_alt'     => 'Ransel terbuka dengan perlengkapan latihan di dalamnya',
                'tags'          => ['perlengkapan-latihan', 'gym'],
                'body'          => $demoNote
                    . '<p>Tas gym yang tersusun rapi menghemat waktu dan membuat latihan terasa lebih nyaman. Berikut daftar perlengkapan yang umum dibawa, dari yang paling esensial sampai pelengkap.</p>'
                    . '<h2>Perlengkapan Dasar</h2>'
                    . '<ul>'
                    . '<li><strong>Botol minum</strong> — pilih ukuran yang cukup untuk satu sesi tanpa harus bolak-balik mengisi.</li>'
                    . '<li><strong>Handuk kecil</strong> — untuk mengelap keringat dan mengalasi bangku mesin.</li>'
                    . '<li><strong>Pakaian ganti</strong> — kaus dan kaus kaki bersih untuk setelah latihan.</li>'
                    . '<li><strong>Sepatu latihan</strong> — jika berbeda dari sepatu harian, simpan sepasang di tas atau loker.</li>'
                    . '</ul>'
                    . '<h2>Pelengkap yang Sering Terlupakan</h2>'
                    . '<ul>'
                    . '<li>Gembok cadangan untuk loker.</li>'
                    . '<li>Kantong kecil untuk pakaian basah atau sepatu kotor.</li>'
                    . '<li>Pengikat rambut atau bando penyerap keringat.</li>'
                    . '<li>Earphone dan pengisi daya portabel.</li>'
                    . '</ul>'
                    . '<h2>Menata Tas Agar Praktis</h2>'
                    . '<p>Gunakan kantong terpisah untuk barang bersih dan barang yang bisa basah. Perlengkapan kecil seperti kunci loker atau earphone mudah hilang di dasar tas — satu pouch kecil menyelesaikan masalah ini.</p>'
                    . '<blockquote>Catat isi tas di jurnal latihan selama seminggu; biasanya terlihat barang mana yang tidak pernah dipakai dan bisa ditinggal di rumah.</blockquote>'
                    . '<p>Dengan tas yang isinya sesuai kebutuhan, tidak ada alasan menunda sesi latihan hanya karena ada barang yang tertinggal.</p>',
            ],
        ];

        $created = 0;
        $skipped = 0;
        foreach ($articles as $def) {
            if ($articleModel->findBySlug($def['slug'])) {
                CLI::write('- Artikel "' . $def['title'] . '" sudah ada, dilewati (tidak ditimpa).', 'dark_gray');
                $skipped++;
                continue;
            }

            $articleModel->insert([
                'title'         => $def['title'],
                'slug'          => $def['slug'],
                'excerpt'       => $def['excerpt'],
                'body'          => $def['body'],
                'cover_image'   => $mediaPaths[$def['cover']] ?? null,
                'cover_alt'     => $def['cover_alt'],
                'category_id'   => $categoryId,
                'detail_layout' => $def['detail_layout'],
                'status'        => $def['status'],
                'published_at'  => date('Y-m-d', strtotime('-' . $def['days_ago'] . ' days')),
            ]);
            $newId = $articleModel->getInsertID();

            $tagIdsForRow = [];
            foreach ($def['tags'] as $tagSlug) {
                $tagIdsForRow[] = $tagIds[$tagSlug];
            }
            $articleModel->syncTags((int) $newId, $tagIdsForRow);

            CLI::write('+ Artikel "' . $def['title'] . '" dibuat (' . $def['status'] . ').', 'green');
            $created++;
        }

        CLI::newLine();
        CLI::write("Selesai: {$created} dibuat, {$skipped} dilewati.", 'yellow');
        CLI::write('Lihat di publik : ' . base_url('blog'), 'cyan');
        CLI::write('Kelola di admin : ' . base_url('admin/articles'), 'cyan');
        CLI::write('Artikel draf bisa dipratinjau lewat tombol Pratinjau di admin (login diperlukan).', 'cyan');
    }
}
