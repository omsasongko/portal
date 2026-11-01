<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedAboutSection extends Migration
{
    public function up()
    {
        // Ensure media record for default about image exists
        $existing = $this->db->table('media')->where('filename', 'assets/portal/uploads/about_04.jpg')->get()->getRow();
        if (! $existing) {
            $this->db->table('media')->insert([
                'title'     => 'About Image',
                'filename'  => 'assets/portal/uploads/about_04.jpg',
                'mime_type' => 'image/jpeg',
            ]);
        }

        $about = $this->db->table('homepage_sections')->where('section', 'about')->get()->getRow();
        if (! $about) {
            $content = json_encode([
                'heading'      => 'About Dominic.',
                'body'         => "Integer rutrum ligula eu dignissim laoreet. Pellentesque venenatis nibh sed tellus faucibus bibendum. Sed fermentum est vitae rhoncus molestie. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Sed vitae rutrum neque. Ut id erat sit amet libero bibendum aliquam. Donec ac egestas libero, eu bibendum risus. Phasellus et congue justo.\n\nSed vitae rutrum neque. Ut id erat sit amet libero bibendum aliquam. Donec ac egestas libero, eu bibendum risus. Phasellus et congue justo.",
                'image'        => 'assets/portal/uploads/about_04.jpg',
                'image_alt'    => '',
                'button_label' => 'Download CV',
                'button_link'  => '#',
                'layout'       => 'image_right',
                'is_enabled'   => true,
            ], JSON_UNESCAPED_UNICODE);

            $this->db->table('homepage_sections')->insert([
                'section'    => 'about',
                'title'      => 'About',
                'layout'     => 'image_right',
                'is_enabled' => 1,
                'sort_order' => 1,
                'content'    => $content,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        $this->db->table('homepage_sections')->where('section', 'about')->delete();
    }
}
