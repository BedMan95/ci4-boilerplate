<?php

namespace App\Modules\Inventory\Controllers;

use App\Controllers\Api\BaseApiController;
use App\Modules\Inventory\Models\InventoryModel;

class InventoryController extends BaseApiController
{
    protected InventoryModel $model;

    public function __construct()
    {
        $this->model = new InventoryModel();
    }

    public function index()
    {
        return $this->respondSuccess($this->model->orderBy('id', 'DESC')->findAll());
    }

    public function show($id = null)
    {
        $item = $this->model->find($id);
        return $item ? $this->respondSuccess($item) : $this->respondError('Not found', 404);
    }

    public function store()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['title'])) {
            return $this->respondError('Judul wajib diisi', 422);
        }

        $id = $this->model->insert([
            'title'   => trim($json['title']),
            'content' => trim($json['content'] ?? ''),
            'status'  => $json['status'] ?? 'active',
        ]);

        return $this->respondSuccess(['id' => $id], 'Data berhasil disimpan', 201);
    }

    public function update($id = null)
    {
        if (!$this->model->find($id)) {
            return $this->respondError('Not found', 404);
        }

        $json = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $this->model->update($id, [
            'title'   => trim($json['title'] ?? ''),
            'content' => trim($json['content'] ?? ''),
            'status'  => $json['status'] ?? 'active',
        ]);

        return $this->respondSuccess(['id' => (int) $id], 'Data berhasil diperbarui');
    }

    public function delete($id = null)
    {
        if (!$this->model->find($id)) {
            return $this->respondError('Not found', 404);
        }
        $this->model->delete($id);
        return $this->respondSuccess(null, 'Data berhasil dihapus');
    }
}