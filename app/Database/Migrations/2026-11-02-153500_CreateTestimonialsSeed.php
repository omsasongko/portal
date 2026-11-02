<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTestimonialsSeed extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        // Ensure the three avatar images exist in the media library so they can
        // be selected again. Guarded: do not insert duplicates.
        $avatars = [
            ['title' => 'Testimonial Avatar 1', 'filename' => 'assets/portal/uploads/testi_01.png'],
            ['title' => 'Testimonial Avatar 2', 'filename' => 'assets/portal/uploads/testi_02.png'],
            ['title' => 'Testimonial Avatar 3', 'filename' => 'assets/portal/uploads/testi_03.png'],
        ];
        foreach ($avatars as $a) {
            $exists = $this->db->table('media')->where('filename', $a['filename'])->get()->getRow();
            if (! $exists) {
                $this->db->table('media')->insert([
                    'title'     => $a['title'],
                    'filename'  => $a['filename'],
                    'mime_type' => 'image/png',
                ]);
            }
        }

        // Seed the testimonials section without overwriting saved data.
        $section = $this->db->table('homepage_sections')->where('section', 'testimonials')->get()->getRow();
        $sectionId = null;

        if (! $section) {
            $content = json_encode([
                'title'    => 'Testimonials',
                'subtitle' => 'We thanks for all our awesome testimonials! There are hundreds of our happy customers! ',
                'layout'   => 'carousel_2_per_slide',
            ], JSON_UNESCAPED_UNICODE);

            $this->db->table('homepage_sections')->insert([
                'section'     => 'testimonials',
                'title'       => 'Testimonials',
                'layout'      => 'carousel_2_per_slide',
                'is_enabled'  => 1,
                'sort_order'  => 4,
                'content'     => $content,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
            $sectionId = (int) $this->db->insertID();
        } else {
            $sectionId = (int) $section->id;
        }

        // Seed the six default testimonial cards only if none exist yet.
        $itemCount = (int) $this->db->table('homepage_section_items')
            ->where('section_id', $sectionId)
            ->countAllResults();

        if ($itemCount > 0) {
            return;
        }

        $a1 = 'assets/portal/uploads/testi_01.png';
        $a2 = 'assets/portal/uploads/testi_02.png';
        $a3 = 'assets/portal/uploads/testi_03.png';

        $items = [
            [
                'quote_heading' => 'Wonderful Support!',
                'quote_body'    => 'They have got my project on time with the competition with a sed highly skilled, and experienced & professional team.',
                'author_name'   => 'James Fernando',
                'author_role'   => 'Manager of Racer',
                'avatar'        => $a1,
            ],
            [
                'quote_heading' => 'Awesome Services!',
                'quote_body'    => 'Explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you completed.',
                'author_name'   => 'Jacques Philips',
                'author_role'   => 'Designer',
                'avatar'        => $a2,
            ],
            [
                'quote_heading' => 'Great & Talented Team!',
                'quote_body'    => 'The master-builder of human happines no one rejects, dislikes avoids pleasure itself, because it is very pursue pleasure. ',
                'author_name'   => 'Venanda Mercy',
                'author_role'   => 'Newyork City',
                'avatar'        => $a3,
            ],
            [
                'quote_heading' => 'Wonderful Support!',
                'quote_body'    => 'They have got my project on time with the competition with a sed highly skilled, and experienced & professional team.',
                'author_name'   => 'James Fernando',
                'author_role'   => 'Manager of Racer',
                'avatar'        => $a1,
            ],
            [
                'quote_heading' => 'Awesome Services!',
                'quote_body'    => 'Explain to you how all this mistaken idea of denouncing pleasure and praising pain was born and I will give you completed.',
                'author_name'   => 'Jacques Philips',
                'author_role'   => 'Designer',
                'avatar'        => $a2,
            ],
            [
                'quote_heading' => 'Great & Talented Team!',
                'quote_body'    => 'The master-builder of human happines no one rejects, dislikes avoids pleasure itself, because it is very pursue pleasure. ',
                'author_name'   => 'Venanda Mercy',
                'author_role'   => 'Newyork City',
                'avatar'        => $a3,
            ],
        ];

        foreach ($items as $i => $item) {
            $this->db->table('homepage_section_items')->insert([
                'section_id' => $sectionId,
                'position'   => $i + 1,
                'content'    => json_encode($item, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        $section = $this->db->table('homepage_sections')->where('section', 'testimonials')->get()->getRow();
        if ($section) {
            $this->db->table('homepage_section_items')->where('section_id', (int) $section->id)->delete();
            $this->db->table('homepage_sections')->where('id', (int) $section->id)->delete();
        }
    }
}