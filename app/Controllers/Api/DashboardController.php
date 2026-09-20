<?php

namespace App\Controllers\Api;

use App\Models\UserModel;

class DashboardController extends BaseApiController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function stats()
    {
        $totalUsers = $this->userModel->countAllResults();
        $adminCount = $this->userModel->where('role', 'admin')->countAllResults();
        $userCount = $this->userModel->where('role', 'user')->countAllResults();

        return $this->respondSuccess([
            'totalUsers' => $totalUsers,
            'adminCount' => $adminCount,
            'userCount'  => $userCount,
        ]);
    }
}
