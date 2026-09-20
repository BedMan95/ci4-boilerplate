<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'status' => 1,
            ],
            [
                'username' => 'user',
                'email' => 'user@example.com',
                'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'status' => 1,
            ],
        ];

        $this->db->table('users')->insertBatch($data);
    }
}