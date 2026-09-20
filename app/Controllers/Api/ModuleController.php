<?php

namespace App\Controllers\Api;

use App\Models\ModuleModel;

class ModuleController extends BaseApiController
{
    protected ModuleModel $moduleModel;

    public function __construct()
    {
        $this->moduleModel = new ModuleModel();
    }

    public function index()
    {
        $modules = $this->moduleModel->orderBy('id', 'DESC')->findAll();
        return $this->respondSuccess($modules);
    }

    public function menu()
    {
        $modules = $this->moduleModel->select('id, name, title, min_level, icon')
            ->orderBy('title', 'ASC')
            ->findAll();
        return $this->respondSuccess($modules);
    }

    public function show($id = null)
    {
        $module = $this->moduleModel->find($id);
        if (!$module) {
            return $this->respondError('Modul tidak ditemukan', 404);
        }
        return $this->respondSuccess($module);
    }

    public function store()
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'name'        => 'required|alpha_dash|min_length[3]|max_length[30]|is_unique[modules.name]',
            'title'       => 'required|min_length[3]|max_length[100]',
            'description' => 'permit_empty|max_length[255]',
            'min_level'   => 'permit_empty|is_natural_no_zero|less_than_equal_to[10]',
        ];

        if (!$this->validateData($json, $rules)) {
            return $this->respondError('Validasi gagal', 422, $this->validator->getErrors());
        }

        $rawName = strtolower(trim($json['name']));
        $studlyName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $rawName)));
        $tableName = str_ends_with($rawName, 's') ? $rawName : $rawName . 's';

        $data = [
            'name'        => $rawName,
            'title'       => trim($json['title']),
            'description' => trim($json['description'] ?? ''),
            'icon'        => trim($json['icon'] ?? 'box'),
            'min_level'   => (int) ($json['min_level'] ?? 1),
        ];

        $moduleId = $this->moduleModel->insert($data);
        if (!$moduleId) {
            return $this->respondError('Gagal menyimpan metadata modul', 500);
        }

        // Generate backend (Controller, Model, Table, Routes) & frontend view
        $this->scaffoldModule($studlyName, $rawName, $tableName, $data['title']);

        return $this->respondSuccess(['id' => $moduleId, 'name' => $rawName], 'Modul berhasil dibuat beserta backend & frontend', 201);
    }

    public function update($id = null)
    {
        $module = $this->moduleModel->find($id);
        if (!$module) {
            return $this->respondError('Modul tidak ditemukan', 404);
        }

        $json = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $rules = [
            'title'       => 'required|min_length[3]|max_length[100]',
            'description' => 'permit_empty|max_length[255]',
            'min_level'   => 'permit_empty|is_natural_no_zero|less_than_equal_to[10]',
        ];

        if (!$this->validateData($json, $rules)) {
            return $this->respondError('Validasi gagal', 422, $this->validator->getErrors());
        }

        $updateData = [
            'title'       => trim($json['title']),
            'description' => trim($json['description'] ?? ''),
            'icon'        => trim($json['icon'] ?? $module['icon']),
            'min_level'   => (int) ($json['min_level'] ?? $module['min_level']),
        ];

        $this->moduleModel->update($id, $updateData);
        return $this->respondSuccess(['id' => (int) $id], 'Modul berhasil diperbarui');
    }

    public function delete($id = null)
    {
        $module = $this->moduleModel->find($id);
        if (!$module) {
            return $this->respondError('Modul tidak ditemukan', 404);
        }

        $rawName = $module['name'];
        $studlyName = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $rawName)));
        $tableName = str_ends_with($rawName, 's') ? $rawName : $rawName . 's';

        // Delete Module files
        $moduleDir = APPPATH . 'Modules/' . $studlyName;
        if (is_dir($moduleDir)) {
            $this->removeDirectory($moduleDir);
        }

        // Drop dynamic table
        $db = \Config\Database::connect();
        $db->query("DROP TABLE IF EXISTS `{$tableName}`");

        // Delete record
        $this->moduleModel->delete($id);

        return $this->respondSuccess(null, 'Modul dan aset berhasil dihapus');
    }

    private function scaffoldModule(string $studly, string $slug, string $table, string $title): void
    {
        $baseDir = APPPATH . 'Modules/' . $studly;
        mkdir($baseDir . '/Controllers', 0755, true);
        mkdir($baseDir . '/Models', 0755, true);

        // 1. Create MySQL table
        $db = \Config\Database::connect();
        $createSql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `content` TEXT NULL,
            `status` VARCHAR(50) DEFAULT 'active',
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $db->query($createSql);

        // 2. Scaffold Model
        $modelCode = <<<PHP
<?php

namespace App\Modules\\{$studly}\Models;

use CodeIgniter\Model;

class {$studly}Model extends Model
{
    protected \$table = '{$table}';
    protected \$primaryKey = 'id';
    protected \$useAutoIncrement = true;
    protected \$returnType = 'array';
    protected \$allowedFields = ['title', 'content', 'status', 'created_at', 'updated_at'];
    protected \$useTimestamps = true;
    protected \$createdField = 'created_at';
    protected \$updatedField = 'updated_at';
}
PHP;
        file_put_contents($baseDir . "/Models/{$studly}Model.php", $modelCode);

        // 3. Scaffold Controller
        $controllerCode = <<<PHP
<?php

namespace App\Modules\\{$studly}\Controllers;

use App\Controllers\Api\BaseApiController;
use App\Modules\\{$studly}\Models\\{$studly}Model;

class {$studly}Controller extends BaseApiController
{
    protected {$studly}Model \$model;

    public function __construct()
    {
        \$this->model = new {$studly}Model();
    }

    public function index()
    {
        return \$this->respondSuccess(\$this->model->orderBy('id', 'DESC')->findAll());
    }

    public function show(\$id = null)
    {
        \$item = \$this->model->find(\$id);
        return \$item ? \$this->respondSuccess(\$item) : \$this->respondError('Not found', 404);
    }

    public function store()
    {
        \$json = \$this->request->getJSON(true) ?? \$this->request->getPost();
        if (empty(\$json['title'])) {
            return \$this->respondError('Judul wajib diisi', 422);
        }

        \$id = \$this->model->insert([
            'title'   => trim(\$json['title']),
            'content' => trim(\$json['content'] ?? ''),
            'status'  => \$json['status'] ?? 'active',
        ]);

        return \$this->respondSuccess(['id' => \$id], 'Data berhasil disimpan', 201);
    }

    public function update(\$id = null)
    {
        if (!\$this->model->find(\$id)) {
            return \$this->respondError('Not found', 404);
        }

        \$json = \$this->request->getJSON(true) ?? \$this->request->getRawInput();
        \$this->model->update(\$id, [
            'title'   => trim(\$json['title'] ?? ''),
            'content' => trim(\$json['content'] ?? ''),
            'status'  => \$json['status'] ?? 'active',
        ]);

        return \$this->respondSuccess(['id' => (int) \$id], 'Data berhasil diperbarui');
    }

    public function delete(\$id = null)
    {
        if (!\$this->model->find(\$id)) {
            return \$this->respondError('Not found', 404);
        }
        \$this->model->delete(\$id);
        return \$this->respondSuccess(null, 'Data berhasil dihapus');
    }
}
PHP;
        file_put_contents($baseDir . "/Controllers/{$studly}Controller.php", $controllerCode);

        // 4. Scaffold Routes
        $routesCode = <<<PHP
<?php

/** @var \CodeIgniter\Router\RouteCollection \$routes */
\$routes->group('api/{$slug}', ['filter' => 'apiAuth'], function (\$routes) {
    \$routes->get('/', '\\App\\Modules\\{$studly}\\Controllers\\{$studly}Controller::index');
    \$routes->post('/', '\\App\\Modules\\{$studly}\\Controllers\\{$studly}Controller::store');
    \$routes->get('(:num)', '\\App\\Modules\\{$studly}\\Controllers\\{$studly}Controller::show/\$1');
    \$routes->put('(:num)', '\\App\\Modules\\{$studly}\\Controllers\\{$studly}Controller::update/\$1');
    \$routes->delete('(:num)', '\\App\\Modules\\{$studly}\\Controllers\\{$studly}Controller::delete/\$1');
});
PHP;
        file_put_contents($baseDir . "/Routes.php", $routesCode);
    }

    private function removeDirectory(string $dir): void
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
