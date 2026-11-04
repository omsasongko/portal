<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateArticlesBlogSection extends Migration
{
    public function up()
    {
        // Articles table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'excerpt' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'body' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'cover_image' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'cover_alt' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'detail_layout' => [
                'type'       => 'ENUM',
                'constraint' => ['full', 'sidebar'],
                'default'    => 'sidebar',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'published'],
                'default'    => 'draft',
            ],
            'published_at' => [
                'type' => 'DATE',
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
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->addKey('category_id');
        $this->forge->createTable('articles');

        // Article <-> tag pivot
        $this->forge->addField([
            'article_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tag_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);
        $this->forge->addKey(['article_id', 'tag_id'], true);
        $this->forge->addKey('tag_id');
        $this->forge->createTable('article_tags');

        // Extend menu_items.type with the "articles" listing target (additive; keeps existing rows).
        $this->db->query("ALTER TABLE menu_items MODIFY type ENUM('section','page','url','articles') NOT NULL");

        // Seed the blog homepage section (guarded — never overwrite saved settings).
        $existing = $this->db->table('homepage_sections')->where('section', 'blog')->get()->getRow();
        if (! $existing) {
            $this->db->table('homepage_sections')->insert([
                'section'    => 'blog',
                'title'      => 'Blog',
                'layout'     => 'grid_3_columns',
                'is_enabled' => 1,
                'sort_order' => 5,
                'content'    => json_encode([
                    'title'       => 'Blog',
                    'subtitle'    => 'Artikel dan catatan terbaru seputar aktivitas kami.',
                    'card_layout' => 'grid_3_columns',
                    'limit'       => '3',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('article_tags', true);
        $this->forge->dropTable('articles', true);
        // Remove listing-type menu items before narrowing the enum back.
        $this->db->table('menu_items')->where('type', 'articles')->delete();
        $this->db->query("ALTER TABLE menu_items MODIFY type ENUM('section','page','url') NOT NULL");
        $this->db->table('homepage_sections')->where('section', 'blog')->delete();
    }
}
