<?php

namespace App\Modules\Inventory\Models;

use CodeIgniter\Model;

class InventoryModel extends Model
{
    protected $table = 'inventorys';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = ['title', 'content', 'status', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}