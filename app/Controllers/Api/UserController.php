<?php

namespace App\Controllers\Api;

use App\Models\UserModel;

class UserController extends BaseApiController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = max(1, min(100, (int) ($this->request->getGet('per_page') ?? 10)));
        $search = trim((string) ($this->request->getGet('search') ?? ''));

        $db = \Config\Database::connect();
        $builder = $db->table('users')
            ->select('id, username, email, role, level, permissions, status, created_at, updated_at');

        if ($search !== '') {
            $builder->groupStart()
                ->like('username', $search)
                ->orLike('email', $search)
                ->groupEnd();
        }

        $total = (clone $builder)->countAllResults();

        $users = $builder->orderBy('created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        $lastPage = (int) ceil($total / $perPage);

        return $this->respondSuccess($users, 'OK', 200, [
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'last_page' => max(1, $lastPage),
        ]);
    }

    public function show($id = null)
    {
        if (!$id) {
            return $this->respondError('ID user diperlukan', 400);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->respondError('User tidak ditemukan', 404);
        }

        $perms = !empty($user->permissions) ? (is_array($user->permissions) ? $user->permissions : json_decode($user->permissions, true)) : [];

        return $this->respondSuccess([
            'id'          => (int) $user->id,
            'username'    => $user->username,
            'email'       => $user->email,
            'role'        => $user->role,
            'level'       => (int) ($user->level ?? 1),
            'permissions' => $perms,
            'status'      => (int) $user->status,
            'created_at'  => $user->created_at,
            'updated_at'  => $user->updated_at,
        ]);
    }

    public function store()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'email'    => 'required|valid_email|is_unique[users.email]',
            'username' => 'required|min_length[3]|max_length[100]',
            'password' => 'required|min_length[8]',
            'role'     => 'required|in_list[admin,user]',
            'level'    => 'permit_empty|is_natural_no_zero|less_than_equal_to[10]',
        ];

        if (!$this->validateData($json, $rules)) {
            return $this->respondError('Validasi gagal', 422, $this->validator->getErrors());
        }

        $permissions = isset($json['permissions']) ? (is_array($json['permissions']) ? json_encode($json['permissions']) : $json['permissions']) : json_encode($json['role'] === 'admin' ? ['*'] : ['read']);

        $data = [
            'email'         => trim($json['email']),
            'username'      => trim($json['username']),
            'password_hash' => password_hash($json['password'], PASSWORD_DEFAULT),
            'role'          => $json['role'],
            'level'         => isset($json['level']) ? (int) $json['level'] : ($json['role'] === 'admin' ? 10 : 1),
            'permissions'   => $permissions,
            'status'        => isset($json['status']) ? (int) $json['status'] : 1,
        ];

        if ($this->userModel->save($data)) {
            $insertedId = $this->userModel->getInsertID();
            return $this->respondSuccess(['id' => $insertedId], 'User berhasil ditambahkan', 201);
        }

        return $this->respondError('Gagal menambahkan user', 500);
    }

    public function update($id = null)
    {
        if (!$id) {
            return $this->respondError('ID user diperlukan', 400);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->respondError('User tidak ditemukan', 404);
        }

        $json = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $rules = [
            'email'    => 'required|valid_email|is_unique[users.email,id,' . $id . ']',
            'username' => 'required|min_length[3]|max_length[100]',
            'role'     => 'required|in_list[admin,user]',
            'level'    => 'permit_empty|is_natural_no_zero|less_than_equal_to[10]',
        ];

        if (!empty($json['password'])) {
            $rules['password'] = 'min_length[8]';
        }

        if (!$this->validateData($json, $rules)) {
            return $this->respondError('Validasi gagal', 422, $this->validator->getErrors());
        }

        $data = [
            'id'         => (int) $id,
            'email'      => trim($json['email']),
            'username'   => trim($json['username']),
            'role'       => $json['role'],
            'level'      => isset($json['level']) ? (int) $json['level'] : (int) ($user->level ?? 1),
            'status'     => isset($json['status']) ? (int) $json['status'] : (int) $user->status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($json['permissions'])) {
            $data['permissions'] = is_array($json['permissions']) ? json_encode($json['permissions']) : $json['permissions'];
        }

        if (!empty($json['password'])) {
            $data['password_hash'] = password_hash($json['password'], PASSWORD_DEFAULT);
        }

        if ($this->userModel->save($data)) {
            return $this->respondSuccess(['id' => (int) $id], 'User berhasil diperbarui');
        }

        return $this->respondError('Gagal memperbarui user', 500);
    }

    public function delete($id = null)
    {
        if (!$id) {
            return $this->respondError('ID user diperlukan', 400);
        }

        if ((int) $id === (int) session()->get('user_id')) {
            return $this->respondError('Tidak dapat menghapus akun sendiri', 403);
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            return $this->respondError('User tidak ditemukan', 404);
        }

        if ($this->userModel->delete($id)) {
            return $this->respondSuccess(null, 'User berhasil dihapus');
        }

        return $this->respondError('Gagal menghapus user', 500);
    }
}
