<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $initialUser = null;
        if (session()->get('isLoggedIn')) {
            $initialUser = [
                'id'       => (int) session()->get('user_id'),
                'username' => session()->get('username'),
                'email'    => session()->get('email'),
                'role'     => session()->get('role'),
            ];
        }

        return view('spa', [
            'initialUser' => $initialUser,
        ]);
    }
}
