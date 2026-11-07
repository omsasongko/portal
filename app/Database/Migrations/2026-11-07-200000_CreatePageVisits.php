<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePageVisits extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'page_type' => [
                'type'       => 'ENUM',
                'constraint' => ['article', 'page'],
            ],
            'page_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'page_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'default'    => '',
            ],
            'browser' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '',
            ],
            'os' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '',
            ],
            'device_type' => [
                'type'       => 'ENUM',
                'constraint' => ['desktop', 'mobile', 'tablet', 'other'],
                'default'    => 'other',
            ],
            'is_bot' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('created_at');
        $this->forge->addKey(['ip_address', 'page_path', 'created_at'], false, false, 'idx_ip_path_time');

        $this->forge->createTable('page_visits');
    }

    public function down()
    {
        $this->forge->dropTable('page_visits');
    }
}
