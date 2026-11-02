<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHomepageSectionItemsSeedServices extends Migration
{
    public function up()
    {
        // Table for repeatable homepage section items (e.g. service cards)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'section_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'position' => [
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
        $this->forge->addKey('section_id');
        $this->forge->createTable('homepage_section_items');

        // Seed the services section and its default cards without overwriting saved data.
        $section = $this->db->table('homepage_sections')->where('section', 'services')->get()->getRow();

        $now = date('Y-m-d H:i:s');
        $description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';

        if (! $section) {
            $content = json_encode([
                'title'    => 'Services',
                'subtitle' => 'Quisque eget nisl id nulla sagittis auctor quis id. Aliquam quis vehicula enim, non aliquam risus.',
                'layout'   => 'grid_3_columns',
            ], JSON_UNESCAPED_UNICODE);

            $this->db->table('homepage_sections')->insert([
                'section'     => 'services',
                'title'       => 'Services',
                'layout'      => 'grid_3_columns',
                'is_enabled'  => 1,
                'sort_order'  => 2,
                'content'     => $content,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $sectionId = (int) $this->db->insertID();

            $cards = [
                ['icon' => 'flaticon-seo',            'title' => 'Web Development', 'description' => $description],
                ['icon' => 'flaticon-development',    'title' => 'Responsive Design', 'description' => $description],
                ['icon' => 'flaticon-process',        'title' => 'Creative Design', 'description' => $description],
                ['icon' => 'flaticon-discuss-issue',  'title' => 'Support', 'description' => $description],
                ['icon' => 'flaticon-idea',           'title' => 'Web Idea', 'description' => $description],
                ['icon' => 'flaticon-idea-1',         'title' => 'Graphic Design', 'description' => $description],
            ];

            $rows = [];
            foreach ($cards as $i => $card) {
                $rows[] = [
                    'section_id' => $sectionId,
                    'position'   => $i + 1,
                    'content'    => json_encode($card, JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->db->table('homepage_section_items')->insertBatch($rows);

            return;
        }

        // Section exists: seed default cards only if there are none yet.
        $sectionId = (int) $section->id;
        $existing = $this->db->table('homepage_section_items')->where('section_id', $sectionId)->countAllResults();

        if ((int) $existing === 0) {
            $cards = [
                ['icon' => 'flaticon-seo',            'title' => 'Web Development', 'description' => $description],
                ['icon' => 'flaticon-development',    'title' => 'Responsive Design', 'description' => $description],
                ['icon' => 'flaticon-process',        'title' => 'Creative Design', 'description' => $description],
                ['icon' => 'flaticon-discuss-issue',  'title' => 'Support', 'description' => $description],
                ['icon' => 'flaticon-idea',           'title' => 'Web Idea', 'description' => $description],
                ['icon' => 'flaticon-idea-1',         'title' => 'Graphic Design', 'description' => $description],
            ];

            $rows = [];
            foreach ($cards as $i => $card) {
                $rows[] = [
                    'section_id' => $sectionId,
                    'position'   => $i + 1,
                    'content'    => json_encode($card, JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->db->table('homepage_section_items')->insertBatch($rows);
        }
    }

    public function down()
    {
        $this->forge->dropTable('homepage_section_items', true);
        $this->db->table('homepage_sections')->where('section', 'services')->delete();
    }
}