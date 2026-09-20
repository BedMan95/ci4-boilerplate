<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'App\Entities\User';
    protected $useSoftDeletes = false;
    protected $allowEmptyMethods = ['findAll', 'first'];
    protected $skipValidation = true;
    protected $allowedFields = [
        'username',
        'email',
        'password_hash',
        'role',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $validate = [
            'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
            'username' => 'required|min_length[3]|max_length[100]',
            'password_hash' => 'required|min_length[8]',
    ];
        protected $validationMessages = [
            'email' => [
                'required' => 'Email harus diisi',
                'valid_email' => 'Format email tidak valid',
                'is_unique' => 'Email sudah terdaftar',
            ],
            'username' => [
                'required' => 'Username harus diisi',
                'min_length' => 'Username minimal 3 karakter',
                'max_length' => 'Username maksimal 100 karakter',
            ],
            'password_hash' => [
                'required' => 'Password harus diisi',
                'min_length' => 'Password minimal 8 karakter',
            ],
        ];

    public function findByEmail(string $email)
    {
        return $this->where('email', $email)->first();
    }

    public function findByUsername(string $username)
    {
        return $this->where('username', $username)->first();
    }

    public function getUsersByRole(string $role)
    {
        return $this->where('role', $role)->findAll();
    }

    public function search(string $keyword)
    {
        return $this->like('username', $keyword)
                    ->orLike('email', $keyword)
                    ->findAll();
    }

    public function countUsers(): int
    {
        return $this->countAll();
    }

    public function countByRole(string $role): int
    {
        return $this->where('role', $role)->countAllResults();
    }
}