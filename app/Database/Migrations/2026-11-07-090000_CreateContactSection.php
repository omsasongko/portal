<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContactSection extends Migration
{
    public function up()
    {
        $existing = $this->db->table('homepage_sections')->where('section', 'contact')->get()->getRow();
        if ($existing) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('homepage_sections')->insert([
            'section'    => 'contact',
            'title'      => 'Contact',
            'layout'     => 'contact_form',
            'is_enabled' => 1,
            'sort_order' => 6,
            'content'    => json_encode([
                'heading'             => 'Contact',
                'description'         => 'Quisque eget nisl id nulla sagittis auctor quis id. Aliquam quis vehicula enim, non aliquam risus.',
                'name_placeholder'    => 'Your Name',
                'email_placeholder'   => 'Your Email',
                'phone_placeholder'   => 'Your Phone',
                'message_placeholder' => 'Your Message',
                'submit_label'        => 'Send Message',
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down()
    {
        $this->db->table('homepage_sections')->where('section', 'contact')->delete();
    }
}
