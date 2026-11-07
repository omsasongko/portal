<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add 'admin_login' to the page_type ENUM so login-page and
 * entry-gate visits can be recorded in page_visits.
 */
class AddAdminLoginPageType extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `page_visits` MODIFY COLUMN `page_type` ENUM('article','page','home','section','blog','admin_login') NOT NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `page_visits` MODIFY COLUMN `page_type` ENUM('article','page','home','section','blog') NOT NULL");
    }
}
