<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLevelAndPermissionsToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'level' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
                'after'      => 'role',
            ],
            'permissions' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'level',
            ],
        ];

        if (!$this->db->fieldExists('level', 'users')) {
            $this->forge->addColumn('users', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('level', 'users')) {
            $this->forge->dropColumn('users', ['level', 'permissions']);
        }
    }
}
