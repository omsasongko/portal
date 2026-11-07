<?php

namespace App\Commands;

use App\Controllers\Admin\Traits\SanitizesHtml;
use App\Models\ArticleModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Temporary seeding helper (2026-11-06): creates the four sample articles
 * requested for testing. Uses the exact same persistence path as the admin
 * editor: ArticleModel::insert + syncTags, with bodies passed through the
 * shared SanitizesHtml trait. Idempotent: articles whose slugs already exist
 * are skipped, so re-running only fills in the missing ones.
 */
class SeedSampleArticles extends BaseCommand
{
    use SanitizesHtml;

    protected $group       = 'Demo';
    protected $name        = 'articles:seed-samples';
    protected $description = 'Creates the four sample articles (3 published, 1 draft); skips slugs that already exist.';

    public function run(array $params)
    {
        $today = date('Y-m-d');

        $articles = [
            [
                'title'   => 'Menata Meja Kerja dalam 15 Menit',
                'slug'    => 'menata-meja-kerja-dalam-15-menit',
                'excerpt' => 'Rutinitas singkat lima belas menit untuk menata kabel, memosisikan monitor, dan menata barang yang sering dipakai di meja kerja.',
                'status'  => 'published',
                'tags'    => [2],
                'body'    => '<p>Meja kerja berantakan membuat fokus pecah sebelum pekerjaan dimulai. Kabar baiknya, merapikannya tidak butuh waktu lama. Siapkan timer lima belas menit dan ikuti rutinitas berikut.</p><p>Menit 1–3: benahi kabel. Lepaskan kabel yang tidak terpakai, satukan kabel daya dengan kabel ikat, lalu tempelkan label kecil agar mudah dikenali. Pastikan tidak ada kabel melintang di jalur kaki.</p><p>Menit 4–8: atur posisi monitor. Letakkan layar sejajar mata, berjarak sekitar satu lengan dari wajah. Condongkan sedikit ke belakang supaya leher tetap rileks. Jika memakai laptop, tambahkan tumpuan sehingga layar naik ke arah pandangan.</p><p>Menit 9–15: tata barang yang sering dipakai. Pena, buku catatan, dan pengisi daya sebaiknya berada dalam jangkauan tangan. Simpan dokumen kertas ke satu map, kosongkan gelas bekas, dan sisakan satu objek kecil yang menyenangkan di meja, misalnya foto keluarga atau tanaman mungil.</p><p>Terakhir, tarik napas sejenak dan perhatikan hasilnya. Meja yang lega membuat pikiran ikut lega. Ulangi rutinitas ini setiap sore sebelum pulang supaya keadaan rapi bertahan lebih lama.</p>',
            ],
            [
                'title'   => 'Akhir Pekan di Subang: Jalan Pagi dan Berburu Foto',
                'slug'    => 'akhir-pekan-di-subang-jalan-pagi-dan-berburu-foto',
                'excerpt' => 'Rute jalan pagi santai di Subang beserta subjek foto menarik di sepanjang jalan, dari kabut sawah hingga aktivitas pasar tradisional.',
                'status'  => 'published',
                'tags'    => [2],
                'body'    => '<p>Subang pagi hari punya ritme yang menenangkan. Mulailah perjalanan akhir pekan sekitar pukul enam, ketika udara masih sejuk dan jalan-jalan kecil belum ramai. Berjalan santai dari alun-alun menuju kawasan pasar tradisional adalah pilihan rute yang mudah sekaligus menyenangkan.</p><p>Sepanjang rute, ada beberapa subjek foto yang menarik untuk dikejar. Pertama, kabut tipis di atas sawah di pinggir kota; manfaatkan cahaya lembut sebelum matahari naik tinggi. Kedua, gerobak-gerobak tepi jalan yang baru dibuka: uap dari dapur gorengan menciptakan latar dramatis untuk foto close-up. Ketiga, aktivitas pedagang sayur di pasar; wajah-wajah yang sibuk memberi kesan hidup pada foto jalanan Anda.</p><p>Bawa satu lensa bantu saja, misalnya lensa 35mm, supaya tas tetap ringan dan kaki mau melangkah lebih jauh. Baterai cadangan dan kain lap kecil juga berguna karena embun pagi di Subang cukup tebal.</p><p>Sebagai penutup rute, berhentilah di warung kopi pinggir jalan. Sambil menyeruput kopi tubruk, pilih sepuluh foto terbaik dan hapus sisanya. Pagi itu terasa lengkap: kaki pegal yang sehat, kartu memori penuh, dan pikiran yang jauh lebih tenang.</p>',
            ],
            [
                'title'   => 'Cara Membuat Cadangan Foto Keluarga',
                'slug'    => 'cara-membuat-cadangan-foto-keluarga',
                'excerpt' => 'Panduan menata folder foto, menyiapkan salinan kedua, dan menguji cadangan agar kenangan keluarga benar-benar aman.',
                'status'  => 'published',
                'tags'    => [],
                'body'    => '<p>Koleksi foto keluarga sering kali hanya hidup di satu tempat: ponsel. Begitu perangkat hilang atau rusak, kenangan bertahun-tahun bisa lenyap dalam sekejap. Membuat cadangan yang rapi ternyata tidak serumit kedengarannya.</p><p>Langkah pertama, rapikan folder. Kelompokkan foto berdasarkan tahun, lalu buat subfolder per peristiwa, misalnya 2025/Lebaran atau 2025/Libur-Bali. Hapus duplikat dan tangkapan layar yang tidak perlu agar ukuran cadangan tetap wajar dan mudah disalin.</p><p>Langkah kedua, siapkan salinan kedua di tempat terpisah. Salin seluruh folder ke hard disk eksternal, lalu simpan di rak yang jauh dari laptop harian. Kalau memungkinkan, tambahkan satu salinan di layanan awan sebagai proteksi tambahan.</p><p>Langkah ketiga, uji cadangan tersebut. Buka beberapa berkas secara acak dari salinan kedua — bukan dari folder asli. Pastikan berkas terbuka utuh, tidak korup, dan warnanya benar. Periksa juga satu video singkat sampai selesai diputar.</p><p>Terakhir, jadwalkan pembaruan bulanan. Tandai tanggal tertentu di kalender, salin foto baru, dan ulangi uji kecil tadi. Cadangan yang tidak pernah diuji belum bisa disebut cadangan; ia hanyalah cermin harapan.</p>',
            ],
            [
                'title'   => 'Catatan Uji Editor: Gambar dan Video YouTube',
                'slug'    => 'catatan-uji-editor-gambar-dan-video-youtube',
                'excerpt' => 'Draf uji untuk fitur editor: gambar pustaka media 25% rata tengah, baris kosong yang disengaja, dan penyematan video YouTube responsif.',
                'status'  => 'draft',
                'tags'    => [],
                'body'    => '<p>Catatan ini dipakai untuk menguji fitur editor artikel sebelum konten asli ditulis. Perhatikan urutan elemen di bawah: paragraf pengantar, gambar dari pustaka media dengan lebar 25 persen rata tengah, takarir singkat, satu baris kosong yang disengaja, lalu video YouTube tersemat. Semua elemen seharusnya tampil konsisten di editor maupun halaman publik.</p><p><img src="http://127.0.0.1:8081/assets/portal/uploads/media/1793981635_2f36badbd5568990f040.jpg" alt="Contoh gambar uji editor" class="img-fluid img-align-center" style="width: 25%; max-width: 100%; height: auto;"></p><p>Gambar di atas sengaja diatur 25 persen agar perbedaan ukuran mudah diperiksa.</p><p>&nbsp;</p><div class="video-embed-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%"><iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" width="560" height="315" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="allowfullscreen" title="YouTube video" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%"></iframe></div><p>Video YouTube di atas memastikan penyematan responsif tetap berfungsi; baris kosong sebelum video sengaja dipertahankan sebagai pemisah.</p>',
            ],
        ];

        $articleModel = new ArticleModel();
        $created = [];
        $skipped = [];

        foreach ($articles as $a) {
            if ($articleModel->findBySlug($a['slug'])) {
                $skipped[] = $a['slug'];
                continue;
            }

            $data = [
                'title'         => $a['title'],
                'slug'          => $a['slug'],
                'excerpt'       => $a['excerpt'],
                'body'          => $this->sanitizeHtml($a['body']),
                'cover_image'   => null,
                'cover_alt'     => null,
                'category_id'   => 3,
                'detail_layout' => 'sidebar',
                'status'        => $a['status'],
                'published_at'  => $a['status'] === 'published' ? $today : null,
            ];

            $id = $articleModel->insert($data, true);
            $articleModel->syncTags((int) $id, $a['tags']);
            // Keep the article_categories pivot consistent with the legacy
            // category_id column for multi-category support.
            $articleModel->syncCategories((int) $id, $a['category_id'] ? [(int) $a['category_id']] : []);
            $created[] = ['id' => $id, 'slug' => $a['slug'], 'status' => $a['status']];
        }

        foreach ($created as $c) {
            CLI::write("DIBUAT  id={$c['id']}  [{$c['status']}]  {$c['slug']}", 'green');
        }
        foreach ($skipped as $slug) {
            CLI::write("LEWATI (slug sudah ada): {$slug}", 'yellow');
        }

        CLI::write(count($created) . ' dibuat, ' . count($skipped) . ' dilewati.', 'white');
        return EXIT_SUCCESS;
    }
}
