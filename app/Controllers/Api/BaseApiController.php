<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class BaseApiController extends BaseController
{
    use ResponseTrait;

    protected function respondSuccess($data = null, string $message = 'OK', int $statusCode = 200, array $meta = [])
    {
        $payload = [
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ];

        if (!empty($meta)) {
            $payload['meta'] = $meta;
        }

        return $this->respond($payload, $statusCode);
    }

    protected function respondError(string $message = 'Bad Request', int $statusCode = 400, $errors = null)
    {
        $payload = [
            'status'  => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return $this->respond($payload, $statusCode);
    }
}
