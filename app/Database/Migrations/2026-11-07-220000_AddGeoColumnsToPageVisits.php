<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add approximate geolocation columns to page_visits.
 *
 * All columns are nullable VARCHAR with NULL default so existing
 * rows are untouched. Location data is populated at insert time by
 * VisitorTracker using a local MMDB database lookup.
 */
class AddGeoColumnsToPageVisits extends Migration
{
    public function up()
    {
        $this->forge->addColumn('page_visits', [
            'country' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'is_bot',
            ],
            'region' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'country',
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'region',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('page_visits', ['country', 'region', 'city']);
    }
}
