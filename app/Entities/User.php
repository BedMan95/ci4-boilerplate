<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $attributes = [
        'id' => null,
        'username' => null,
        'email' => null,
        'password_hash' => null,
        'role' => 'user',
        'level' => 1,
        'permissions' => null,
        'status' => 1,
        'created_at' => null,
        'updated_at' => null,
        'deleted_at' => null,
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'id' => 'int',
        'level' => 'int',
        'status' => 'int',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function setEmail(string $email): self
    {
        $this->attributes['email'] = strtolower($email);
        return $this;
    }

    public function setPasswordHash(string $password): self
    {
        $this->attributes['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->attributes['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->attributes['password_hash']);
    }

    public function hasRole(string $role): bool
    {
        return $this->attributes['role'] === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}