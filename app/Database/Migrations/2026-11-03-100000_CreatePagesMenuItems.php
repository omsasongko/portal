<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePagesMenuItems extends Migration
{
    public function up()
    {
        // Pages table
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
            'content' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'published'],
                'default'    => 'draft',
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
        $this->forge->createTable('pages');

        // Menu items table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['section', 'page', 'url'],
            ],
            'target' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'is_visible' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
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
        $this->forge->createTable('menu_items');

        // Seed default menu items (only if table is empty)
        if ($this->db->table('menu_items')->countAllResults() === 0) {
            $now = date('Y-m-d H:i:s');

            $this->db->table('menu_items')->insertBatch([
                ['label' => 'Home',         'type' => 'section', 'target' => 'home',         'sort_order' => 0, 'is_visible' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['label' => 'About Us',     'type' => 'section', 'target' => 'about',        'sort_order' => 1, 'is_visible' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['label' => 'Services',     'type' => 'section', 'target' => 'services',     'sort_order' => 2, 'is_visible' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['label' => 'Portfolio',    'type' => 'section', 'target' => 'portfolio',    'sort_order' => 3, 'is_visible' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['label' => 'Testimonials', 'type' => 'section', 'target' => 'testimonials', 'sort_order' => 4, 'is_visible' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['label' => 'Blog',         'type' => 'section', 'target' => 'blog',         'sort_order' => 5, 'is_visible' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['label' => 'Contact Us',   'type' => 'section', 'target' => 'contact',      'sort_order' => 6, 'is_visible' => 1, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('menu_items', true);
        $this->forge->dropTable('pages', true);
    }
}
