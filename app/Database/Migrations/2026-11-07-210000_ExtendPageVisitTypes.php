<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Extend the page_type ENUM to support homepage, section, and blog
 * listing tracking in addition to existing 'article' and 'page' types.
 *
 * Existing records are NOT modified — only the allowed values change.
 * The page_id column is also relaxed to allow 0 for pages without
 * a database record (homepage, sections, blog listing).
 */
class ExtendPageVisitTypes extends Migration
{
    public function up()
    {
        // Extend ENUM: add 'home', 'section', 'blog' while keeping 'article', 'page'.
        $this->db->query("ALTER TABLE `page_visits` MODIFY COLUMN `page_type` ENUM('article','page','home','section','blog') NOT NULL");
    }

    public function down()
    {
        // Revert to original ENUM (records with new types will become '' in strict mode).
        $this->db->query("ALTER TABLE `page_visits` MODIFY COLUMN `page_type` ENUM('article','page') NOT NULL");
    }
}
