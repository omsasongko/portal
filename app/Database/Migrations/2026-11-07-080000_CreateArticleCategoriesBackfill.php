<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Articles previously stored a single optional category_id. Multi-category
 * support reuses the exact relationship pattern already used for tags
 * (article_tags pivot): a new article_categories pivot becomes the source of
 * truth, and every existing single assignment is backfilled into it so an
 * article assigned to e.g. "Fitness 2" keeps that category checked after the
 * upgrade. The legacy articles.category_id column stays and is kept in sync
 * as the primary (first selected) category by the save flow.
 */
class CreateArticleCategoriesBackfill extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'article_id' => [
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
        $this->forge->addKey(['article_id', 'category_id'], true);
        $this->forge->addKey('category_id');
        $this->forge->createTable('article_categories');

        // Preserve every existing assignment (one row per article that had a
        // category). No-op for articles without one.
        $this->db->query(
            'INSERT INTO article_categories (article_id, category_id)
             SELECT id, category_id FROM articles WHERE category_id IS NOT NULL'
        );
    }

    public function down()
    {
        $this->forge->dropTable('article_categories', true);
    }
}
