<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiteSettingsMediaHomepageSections extends Migration
{
    public function up()
    {
        // Media table (minimal library for settings uploads)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('media');

        // Site settings table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'group' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'site_identity',
            ],
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['group', 'key']);
        $this->forge->createTable('site_settings');

        // Homepage sections table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'section' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'layout' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'is_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'content' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('homepage_sections');

        // Seed default media
        $mediaData = [
            ['title' => 'Logo', 'filename' => 'assets/portal/images/logo.png', 'mime_type' => 'image/png'],
            ['title' => 'Favicon', 'filename' => 'assets/portal/images/favicon.ico', 'mime_type' => 'image/x-icon'],
            ['title' => 'Apple Touch Icon', 'filename' => 'assets/portal/images/apple-touch-icon.png', 'mime_type' => 'image/png'],
            ['title' => 'Hero Background', 'filename' => 'assets/portal/uploads/banner-01.jpg', 'mime_type' => 'image/jpeg'],
        ];
        $this->db->table('media')->insertBatch($mediaData);

        $logo       = $this->db->table('media')->where('filename', 'assets/portal/images/logo.png')->get()->getRow();
        $favicon    = $this->db->table('media')->where('filename', 'assets/portal/images/favicon.ico')->get()->getRow();
        $apple      = $this->db->table('media')->where('filename', 'assets/portal/images/apple-touch-icon.png')->get()->getRow();
        $heroBg     = $this->db->table('media')->where('filename', 'assets/portal/uploads/banner-01.jpg')->get()->getRow();

        $now = date('Y-m-d H:i:s');

        $this->db->table('site_settings')->insertBatch([
            ['group' => 'site_identity', 'key' => 'site_title', 'value' => 'Dominic - Responsive HTML5 OnePage Template', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'site_identity', 'key' => 'site_name', 'value' => 'Dominic', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'site_identity', 'key' => 'meta_keywords', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'site_identity', 'key' => 'meta_description', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'site_identity', 'key' => 'meta_author', 'value' => '', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'site_identity', 'key' => 'logo', 'value' => $logo ? $logo->filename : '', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'site_identity', 'key' => 'favicon', 'value' => $favicon ? $favicon->filename : '', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'site_identity', 'key' => 'apple_touch_icon', 'value' => $apple ? $apple->filename : '', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'global_appearance', 'key' => 'show_preloader', 'value' => '1', 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'global_appearance', 'key' => 'show_scroll_to_top', 'value' => '1', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $heroContent = json_encode([
            'heading'            => "hello i'm Dominic",
            'subheading'         => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, \nsed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
            'use_rotating_words' => true,
            'rotating_words'     => ['Web Developer', 'Web Design', 'Creative Design', 'Graphic Design'],
            'background_image'   => $heroBg ? $heroBg->filename : 'assets/portal/uploads/banner-01.jpg',
            'button_label'       => '',
            'button_link'        => '',
        ]);

        $this->db->table('homepage_sections')->insert([
            'section'     => 'hero',
            'title'       => 'Hero',
            'layout'      => 'hero_rotating',
            'is_enabled'  => 1,
            'sort_order'  => 0,
            'content'     => $heroContent,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('homepage_sections', true);
        $this->forge->dropTable('site_settings', true);
        $this->forge->dropTable('media', true);
    }
}
