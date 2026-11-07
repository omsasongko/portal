<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create the contact_messages table for storing public contact form
 * submissions for admin review.
 */
class CreateContactMessages extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'is_read' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('is_read');
        $this->forge->addKey('created_at');
        $this->forge->createTable('contact_messages', true);
    }

    public function down()
    {
        $this->forge->dropTable('contact_messages', true);
    }
}
