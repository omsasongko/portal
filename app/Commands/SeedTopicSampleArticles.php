<?php

namespace App\Commands;

use App\Controllers\Admin\Traits\SanitizesHtml;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\TagModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Temporary seeding helper (2026-11-07): completes the requested set of
 * 15 Indonesian sample articles across the Kesehatan, Teknologi, and Gaya
 * Hidup categories (5 each), with relevant tags from the global tag pool.
 * Uses the exact same persistence path as the admin editor:
 * ArticleModel::insert + syncTags + syncCategories, bodies passed through
 * the shared SanitizesHtml trait. Idempotent: slugs that already exist are
 * skipped, and the Gaya Hidup category / tags are reused when present, so
 * re-running never duplicates anything.
 */
class SeedTopicSampleArticles extends BaseCommand
{
    use SanitizesHtml;

    protected $group       = 'Demo';
    protected $name        = 'articles:seed-topic-samples';
    protected $description = 'Creates 15 sample articles (5x Kesehatan, Teknologi, Gaya Hidup); skips slugs that already exist.';

    /**
     * Topic => [category name, relevant tag slugs, articles (title, excerpt,
     * body, optional cover from the existing media library)].
     */
    private const TOPICS = [
        'Kesehatan' => [
            'category' => 'Kesehatan',
            'tags'     => ['gizi-seimbang', 'tidur-berkualitas', 'kesehatan-mental'],
            'articles' => [
                ['Menjaga Imun Tubuh di Musim Pancaroba', 'Tidur cukup, makan bergizi, dan gerak ringan adalah tiga fondasi imun yang bisa dimulai hari ini.', '<p>Saat musim berganti, tubuh mudah merasa lelah dan rentan sakit. Kabar baiknya, menjaga imun tidak butuh langkah rumit: cukup tidur tujuh sampai delapan jam, piring makan berwarna, dan tiga puluh menit berjalan kaki setiap hari.</p><p>Cuci tangan dengan benar, perbanyak air putih, dan jangan tunda istirahat saat tubuh mulai memberi sinyal. Kebiasaan kecil yang konsisten selalu mengalahkan solusi instan.</p>', 'assets/portal/uploads/gallery_img-01.jpg'],
                ['Panduan Gizi Seimbang untuk Keluarga Sibuk', 'Menyusun piring "isi pingan" sederhana agar kebutuhan gizi keluarga tetap terpenuhi di tengah jadwal padat.', '<p>Gizi seimbang tidak berarti hidangan mahal. Gunakan panduan isi pingan: sepertiga karbohidrat, sepertiga lauk protein, dan sepertiga sayur plus buah pelengkap.</p><p>Rencanakan menu mingguan, belanja sekali dengan daftar, dan siapkan sayur cuci di kulkas agar mudah diambil kapan saja. Kunci utamanya adalah rutinitas yang realistis, bukan kesempurnaan.</p>', null],
                ['Tidur Berkualitas: Kebiasaan Kecil yang Mengubah Hari', 'Jam tidur yang konsisten, kamar yang gelap, dan layar yang mundur teratur membantu tubuh pulih lebih baik.', '<p>Tidur berkualitas adalah investasi kesehatan yang paling murah. Cobanya sederhana: tidur dan bangun di jam yang sama setiap hari, turunkan cahaya kamar satu jam sebelum tidur, dan sisihkan ponsel dari jangkauan tangan.</p><p>Jika pikiran masih sibuk, tulis urusan esok di kertas lalu tutup bukunya. Tubuh butuh sinyal jelas bahwa hari sudah selesai.</p>', null],
                ['Mengelola Stres Kerja demi Kesehatan Mental', 'Tanda-tanda awal kelelahan, cara menetapkan batas yang sehat, dan kapan meminta bantuan profesional.', '<p>Stres kerja yang dibiarkan mengendap perlahan mengikis energi dan fokus. Kenali tandanya lebih awal: susah konsentrasi, mudah tersinggung, dan tidur yang tidak nyenyak.</p><p>Tetapkan batas yang jelas antara kerja dan istirahat, ambil jeda singkat setiap sembilan puluh menit, dan bicarakan beban dengan orang terdekat. bila rasa berat tidak kunjung reda, berkonsultasilah dengan profesional — itu tanda kekuatan, bukan kelemahan.</p>', null],
                ['Cukup Air, Cukup Energi: Kebiasaan Hidrasi Harian', 'Tips sederhana memenuhi kebutuhan cairan harian tanpa merasa dipaksa minum.', '<p>Dehidrasi ringan saja sudah membuat tubuh terasa lelah dan kepala terasa berat. Cara paling mudah: satu gelas air setiap bangun tidur, satu sebelum makan siang, dan satu lagi sebelum sore.</p><p>Simpan botol di meja kerja sebagai pengingat visual, dan jadikan teh tanpa gula sebagai alternatif bila bosan dengan air biasa.</p>', 'assets/portal/uploads/gallery_img-02.jpg'],
            ],
        ],
        'Teknologi' => [
            'category' => 'Teknologi',
            'tags'     => ['kecerdasan-buatan', 'keamanan-digital', 'perangkat-pintar'],
            'articles' => [
                ['Mengenal Kecerdasan Buatan dalam Kehidupan Sehari-hari', 'Dari rekomendasi lagu sampai asisten tulis mengetik: cara AI bekerja diam-diam di sekitar kita.', '<p>Kecerdasan buatan sering terdengar seperti teknologi masa depan, padahal kita memakainya setiap hari: peta yang memprediksi kemacetan, kamera yang mengenali wajah, dan keyboard yang melengkapi kalimat.</p><p>Pahami bahwa AI adalah alat bantu — hasilnya perlu kita periksa kembali. Semakin kita paham cara kerjanya, semakin bijak kita memanfaatkannya.</p>', 'assets/portal/uploads/gallery_img-03.jpg'],
                ['Menjaga Keamanan Digital: 7 Kebiasaan Wajib', 'Kata sandi unik, verifikasi dua langkah, dan kebiasaan curiga terhadap tautan mencurigakan.', '<p>Sebagian besar pembobolan akun bermula dari kebiasaan sederhana yang diabaikan. Mulailah dari tujuh hal ini: kata sandi unik per akun, pengelola kata sandi, verifikasi dua langkah, pembaruan perangkat, cadangan rutin, curiga pada tautan, dan tinjau aplikasi yang punya akses ke akun Anda.</p><p>Keamanan digital bukan soal alat mahal, melainkan kebiasaan yang dijalankan konsisten.</p>', null],
                ['Rumah Pintar yang Benar-Benar Membantu', 'Memulai perangkat pintar dari kebutuhan nyata, bukan sekadar ikut-ikutan tren.', '<p>Rumah pintar paling membantu ketika dimulai dari masalah nyata: lampu yang lupa dimatikan, pintu yang ingin dipantau, atau jadwal pendingin ruangan yang ingin diatur jarak jauh.</p><p>Mulai dari satu perangkat, pastikan semua bisa dikontrol dari satu aplikasi, lalu tambahkan bertahap. Jangan lupa amankan Wi-Fi rumah karena semua perangkat bergantung padanya.</p>', null],
                ['Tips Memilih Gadget Sesuai Kebutuhan', 'Spesifikasi tinggi tidak selalu berarti cocok: cocokkan perangkat dengan cara Anda bekerja.', '<p>Sebelum membeli gadget baru, tulis dulu tiga aktivitas yang paling sering Anda lakukan. Baterai tahan lama untuk yang sering berpindah tempat, layar nyaman untuk yang banyak membaca, dan kamera baik untuk yang suka mendokumentasikan.</p><p>Hindari membeli karena spesifikasi tertinggi; belilah karena kebutuhan yang jelas. Gadget terbaik adalah yang terasa tidak terasa saat dipakai.</p>', null],
                ['Data Pribadi di Internet: Apa yang Sebaiknya Dijaga', 'Memilah informasi yang aman dibagikan dan menyetel privasi akun secara berkala.', '<p>Tidak semua data pribadi bernilai risiko yang sama. Alamat rumah, nomor identitas, dan lokasi realtime layak dijaga ketat; sementara nama dan opini publik umumnya lebih bebas dibagikan.</p><p>Tinjau setelan privasi setiap enam bulan, hapus aplikasi yang tidak dipakai, dan ingat prinsip sederhana: apa pun yang diunggah ke internet, anggap bisa dibaca siapa saja di masa depan.</p>', 'assets/portal/uploads/gallery_img-04.jpg'],
            ],
        ],
        'Gaya Hidup' => [
            'category' => 'Gaya Hidup',
            'tags'     => ['latihan-kekuatan', 'kardio', 'peregangan'],
            'articles' => [
                ['Memulai Latihan Kekuatan dari Nol', 'Program tiga hari per minggu dengan gerakan dasar yang aman untuk pemula.', '<p>Latihan kekuatan tidak memerlukan alat lengkap. Mulailah dengan squat tanpa beban, push-up di dinding, dan plank dua puluh detik — tiga putaran, tiga kali seminggu.</p><p>Tambah beban atau pengulangan sedikit demi sedikit setiap pekan. Konsistensi tiga bulan akan memberi hasil yang jauh lebih terasa daripada semangat semalam lalu berhenti.</p>', 'assets/portal/uploads/gallery_img-05.jpg'],
                ['Kardio Ringan untuk Pagi yang Lebih Segar', 'Dua puluh menit jalan cepat atau bersepeda santai untuk membangunkan tubuh tanpa menguras energi.', '<p>Kardio pagi tidak harus keringat deras. Jalan cepat keliling kompleks atau bersepeda santai dua puluh menit cukup untuk melancarkan peredaran darah dan memperbaiki suasana hati.</p><p>Kunci keberhasilannya adalah jadwal yang sama setiap pagi dan pakaian olahraga yang sudah disiapkan semalam, sehingga alasan "nanti dulu" tidak punya ruang.</p>', null],
                ['Peregangan 10 Menit untuk Pekerja Meja', 'Rangkaian gerak sederhana untuk leher, bahu, dan punggung yang kaku di akhir hari kerja.', '<p>Duduk berjam-jam membuat leher dan punggung bawah menanggung beban paling besar. Ambil jeda setiap satu jam: angkat bahu sepuluh kali, putar leher perlahan, dan berdiri sejenak.</p><p>Di akhir hari, luangkan sepuluh menit untuk peregangan statis — tahan tiap gerakan dua puluh detik. Punggung akan berterima kasih di esok paginya.</p>', null],
                ['Menata Rutinitas Pagi yang Tenang', 'Bangun lebih awal sedikit, atur tiga prioritas utama, dan mulai hari tanpa kejar-kejaran.', '<p>Rutinitas pagi yang tenang tidak butuh bangun pukul lima. Cukup mundurkan alarm dua puluh menit, hindari ponsel di jam pertama, dan tulis tiga hal terpenting yang harus selesai hari ini.</p><p>Sarapan sederhana dan sepuluh menit merapikan meja kerja juga membantu. Hari yang dimulai tenang cenderung berjalan lebih tertata.</p>', null],
                ['Hidup Rapi: Decluttering Tanpa Drama', 'Metode satu laci satu waktu agar menata rumah terasa ringan dan bertahan lama.', '<p>Decluttering gagal biasanya karena terlalu ambisius: satu rumah dalam satu akhir pekan. Cobalah pendekatan satu laci: pilih satu laci atau satu rak, pisahkan barang menjadi dipakai, disimpan, dan disumbangkan.</p><p>Ulangi seminggu sekali. Dalam dua bulan, rumah terasa lebih lega tanpa perlu hari besar yang melelahkan.</p>', 'assets/portal/uploads/gallery_img-06.jpg'],
            ],
        ],
    ];

    public function run(array $params)
    {
        $articleModel  = new ArticleModel();
        $categoryModel = new CategoryModel();
        $tagModel      = new TagModel();

        // Reuse-or-create the three topic categories (same rules as the
        // admin category workflow).
        $categoryIds = [];
        foreach (['Kesehatan', 'Teknologi', 'Gaya Hidup'] as $categoryName) {
            $slug = mb_url_title($categoryName, '-', true);
            $cat  = $categoryModel->where('slug', $slug)->first()
                ?? $categoryModel->where('name', $categoryName)->first();
            if (! $cat) {
                $id = $categoryModel->insert(['name' => $categoryName, 'slug' => $slug, 'description' => null], true);
                CLI::write("KATEGORI DIBUAT id={$id} {$categoryName} ({$slug})", 'green');
                $categoryIds[$categoryName] = (int) $id;
            } else {
                CLI::write("KATEGORI PAKAI id={$cat['id']} {$cat['name']} ({$cat['slug']})", 'yellow');
                $categoryIds[$categoryName] = (int) $cat['id'];
            }
        }

        // Reuse-or-create the relevant global tags.
        $tagIds = [];
        $wanted = [];
        foreach (self::TOPICS as $topic) {
            $wanted = array_merge($wanted, $topic['tags']);
        }
        foreach (array_unique($wanted) as $tagSlug) {
            $tag = $tagModel->where('slug', $tagSlug)->first();
            if (! $tag) {
                $name = ucwords(str_replace('-', ' ', $tagSlug));
                $id   = $tagModel->insert(['name' => $name, 'slug' => $tagSlug, 'description' => null], true);
                CLI::write("TAG DIBUAT id={$id} {$name} ({$tagSlug})", 'green');
                $tagIds[$tagSlug] = (int) $id;
            } else {
                $tagIds[$tagSlug] = (int) $tag['id'];
            }
        }
        CLI::write('Tag global dipakai: ' . implode(', ', array_keys($tagIds)), 'white');

        $created = [];
        $skipped = [];
        $today   = strtotime(date('Y-m-d'));

        foreach (self::TOPICS as $topic) {
            foreach ($topic['articles'] as $i => $a) {
                $slug = mb_url_title($a[0], '-', true);

                // Check existing samples before inserting anything.
                if ($articleModel->findBySlug($slug)) {
                    $skipped[] = $slug;
                    continue;
                }

                $data = [
                    'title'         => $a[0],
                    'slug'          => $slug,
                    'excerpt'       => $a[1],
                    'body'          => $this->sanitizeHtml($a[2]),
                    'cover_image'   => $a[3] ?? null,
                    'cover_alt'     => $a[3] !== null ? $a[0] : null,
                    'category_id'   => $categoryIds[$topic['category']],
                    'detail_layout' => 'sidebar',
                    'status'        => 'published',
                    'published_at'  => date('Y-m-d', $today - (($i + 1) * 3) * 86400),
                ];

                $id = $articleModel->insert($data, true);
                $articleModel->syncTags((int) $id, array_values(array_intersect_key($tagIds, array_flip($topic['tags']))));
                $articleModel->syncCategories((int) $id, [$categoryIds[$topic['category']]]);
                $created[] = ['id' => $id, 'slug' => $slug, 'category' => $topic['category']];
            }
        }

        foreach ($created as $c) {
            CLI::write("DIBUAT  id={$c['id']}  [{$c['category']}]  {$c['slug']}", 'green');
        }
        foreach ($skipped as $slug) {
            CLI::write("LEWATI (slug sudah ada): {$slug}", 'yellow');
        }

        CLI::write(count($created) . ' dibuat, ' . count($skipped) . ' sudah ada.', 'white');
        return EXIT_SUCCESS;
    }
}
