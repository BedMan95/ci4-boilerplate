<?php

namespace App\Controllers\Api;

use App\Models\UserModel;

class AuthController extends BaseApiController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function me()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->respondError('Unauthorized', 401);
        }

        return $this->respondSuccess([
            'id'          => session()->get('user_id'),
            'username'    => session()->get('username'),
            'email'       => session()->get('email'),
            'role'        => session()->get('role'),
            'level'       => (int) (session()->get('level') ?? 1),
            'permissions' => session()->get('permissions') ?? ['read'],
        ]);
    }

    public function login()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $email = trim($json['email'] ?? '');
        $password = (string) ($json['password'] ?? '');

        if (!$email || !$password) {
            return $this->respondError('Email dan password wajib diisi', 422);
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user->password_hash)) {
            return $this->respondError('Email atau password salah', 401);
        }

        if ((int) $user->status !== 1) {
            return $this->respondError('Akun Anda tidak aktif', 403);
        }

        $perms = !empty($user->permissions) ? (is_array($user->permissions) ? $user->permissions : json_decode($user->permissions, true)) : ($user->role === 'admin' ? ['*'] : ['read']);

        session()->regenerate();
        session()->set([
            'user_id'     => (int) $user->id,
            'username'    => $user->username,
            'email'       => $user->email,
            'role'        => $user->role,
            'level'       => (int) ($user->level ?? 1),
            'permissions' => $perms,
            'isLoggedIn'  => true,
        ]);

        return $this->respondSuccess([
            'id'          => (int) $user->id,
            'username'    => $user->username,
            'email'       => $user->email,
            'role'        => $user->role,
            'level'       => (int) ($user->level ?? 1),
            'permissions' => $perms,
        ], 'Login berhasil');
    }

    public function register()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'email'            => 'required|valid_email|is_unique[users.email]',
            'username'         => 'required|min_length[3]|max_length[100]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (!$this->validateData($json, $rules)) {
            return $this->respondError('Validasi gagal', 422, $this->validator->getErrors());
        }

        $data = [
            'email'         => trim($json['email']),
            'username'      => trim($json['username']),
            'password_hash' => password_hash($json['password'], PASSWORD_DEFAULT),
            'role'          => 'user',
            'level'         => 1,
            'permissions'   => json_encode(['read']),
            'status'        => 1,
        ];

        if ($this->userModel->save($data)) {
            return $this->respondSuccess(null, 'Registrasi berhasil. Silakan login.', 201);
        }

        return $this->respondError('Gagal melakukan registrasi', 500);
    }

    public function logout()
    {
        session()->destroy();
        return $this->respondSuccess(null, 'Berhasil logout');
    }
}
