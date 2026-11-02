<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePortfolioTablesSeed extends Migration
{
    public function up()
    {
        // Portfolio filter categories (separate from article categories)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'filter_class' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'position' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
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
        $this->forge->addUniqueKey('filter_class');
        $this->forge->createTable('portfolio_categories');

        // Portfolio gallery items
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'image_alt' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'link' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'position' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
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
        $this->forge->addKey('position');
        $this->forge->createTable('portfolio_items');

        // Pivot: portfolio item <-> categories
        $this->forge->addField([
            'item_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);
        $this->forge->addKey(['item_id', 'category_id'], true);
        $this->forge->createTable('portfolio_item_categories');

        // Seed the portfolio section, categories, and items without overwriting saved data.
        $section = $this->db->table('homepage_sections')->where('section', 'portfolio')->get()->getRow();
        $now = date('Y-m-d H:i:s');

        // Ensure gallery images exist in media so they can be selected again.
        $galleryImages = [
            ['title' => 'Portfolio 1', 'filename' => 'assets/portal/uploads/gallery_img-01.jpg'],
            ['title' => 'Portfolio 2', 'filename' => 'assets/portal/uploads/gallery_img-02.jpg'],
            ['title' => 'Portfolio 3', 'filename' => 'assets/portal/uploads/gallery_img-03.jpg'],
            ['title' => 'Portfolio 4', 'filename' => 'assets/portal/uploads/gallery_img-04.jpg'],
            ['title' => 'Portfolio 5', 'filename' => 'assets/portal/uploads/gallery_img-05.jpg'],
            ['title' => 'Portfolio 6', 'filename' => 'assets/portal/uploads/gallery_img-06.jpg'],
        ];
        foreach ($galleryImages as $img) {
            $exists = $this->db->table('media')->where('filename', $img['filename'])->get()->getRow();
            if (! $exists) {
                $this->db->table('media')->insert([
                    'title'     => $img['title'],
                    'filename'  => $img['filename'],
                    'mime_type' => 'image/jpeg',
                ]);
            }
        }

        if (! $section) {
            $content = json_encode([
                'title'    => 'Portfolio',
                'subtitle' => 'Quisque eget nisl id nulla sagittis auctor quis id. Aliquam quis vehicula enim, non aliquam risus.',
                'layout'   => 'masonry_3_columns',
            ], JSON_UNESCAPED_UNICODE);

            $this->db->table('homepage_sections')->insert([
                'section'     => 'portfolio',
                'title'       => 'Portfolio',
                'layout'      => 'masonry_3_columns',
                'is_enabled'  => 1,
                'sort_order'  => 3,
                'content'     => $content,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        // Seed categories and items only if the portfolio has no categories yet.
        $catCount = $this->db->table('portfolio_categories')->countAllResults();
        if ((int) $catCount > 0) {
            return;
        }

        $categories = [
            ['name' => 'Web Development', 'filter_class' => 'gal_a'],
            ['name' => 'Creative Design', 'filter_class' => 'gal_b'],
            ['name' => 'Graphic Design',  'filter_class' => 'gal_c'],
        ];

        $catIds = [];
        foreach ($categories as $i => $cat) {
            $this->db->table('portfolio_categories')->insert([
                'name'         => $cat['name'],
                'filter_class' => $cat['filter_class'],
                'position'     => $i + 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
            $catIds[$cat['filter_class']] = (int) $this->db->insertID();
        }

        $items = [
            ['image' => 'assets/portal/uploads/gallery_img-01.jpg', 'image_alt' => 'Image', 'title' => '', 'link' => '', 'classes' => ['gal_a', 'gal_b']],
            ['image' => 'assets/portal/uploads/gallery_img-02.jpg', 'image_alt' => 'Image', 'title' => '', 'link' => '', 'classes' => ['gal_c', 'gal_b']],
            ['image' => 'assets/portal/uploads/gallery_img-03.jpg', 'image_alt' => 'Image', 'title' => '', 'link' => '', 'classes' => ['gal_a', 'gal_c']],
            ['image' => 'assets/portal/uploads/gallery_img-04.jpg', 'image_alt' => 'Image', 'title' => '', 'link' => '', 'classes' => ['gal_b', 'gal_a']],
            ['image' => 'assets/portal/uploads/gallery_img-05.jpg', 'image_alt' => 'Image', 'title' => '', 'link' => '', 'classes' => ['gal_a', 'gal_c']],
            ['image' => 'assets/portal/uploads/gallery_img-06.jpg', 'image_alt' => 'Image', 'title' => '', 'link' => '', 'classes' => ['gal_c', 'gal_a']],
        ];

        foreach ($items as $i => $item) {
            $this->db->table('portfolio_items')->insert([
                'image'      => $item['image'],
                'image_alt'  => $item['image_alt'],
                'title'      => $item['title'],
                'link'       => $item['link'],
                'position'   => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $itemId = (int) $this->db->insertID();

            foreach ($item['classes'] as $cls) {
                if (isset($catIds[$cls])) {
                    $this->db->table('portfolio_item_categories')->insert([
                        'item_id'     => $itemId,
                        'category_id' => $catIds[$cls],
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('portfolio_item_categories', true);
        $this->forge->dropTable('portfolio_items', true);
        $this->forge->dropTable('portfolio_categories', true);
        $this->db->table('homepage_sections')->where('section', 'portfolio')->delete();
    }
}