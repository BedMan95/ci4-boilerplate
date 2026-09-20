<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// SPA Entry Points
$routes->get('/', 'Home::index');
$routes->get('/dashboard', 'Home::index');
$routes->get('/login', 'Home::index');
$routes->get('/register', 'Home::index');
$routes->get('/admin', 'Home::index');
$routes->get('/admin/(:any)', 'Home::index');

// REST API
$routes->group('api', function ($routes) {
    // Auth endpoints
    $routes->post('auth/login', 'Api\AuthController::login');
    $routes->post('auth/register', 'Api\AuthController::register');
    $routes->post('auth/logout', 'Api\AuthController::logout');
    $routes->get('auth/me', 'Api\AuthController::me');

    // Dashboard endpoints (Auth required)
    $routes->get('dashboard/stats', 'Api\DashboardController::stats', ['filter' => 'apiAuth']);

    // User management endpoints (Admin required)
    $routes->group('users', ['filter' => ['apiAuth', 'apiAdmin']], function ($routes) {
        $routes->get('/', 'Api\UserController::index');
        $routes->post('/', 'Api\UserController::store');
        $routes->get('(:num)', 'Api\UserController::show/$1');
        $routes->put('(:num)', 'Api\UserController::update/$1');
        $routes->post('(:num)', 'Api\UserController::update/$1');
        $routes->delete('(:num)', 'Api\UserController::delete/$1');
    });
});

// Logout fallback
$routes->get('/logout', function () {
    session()->destroy();
    return redirect()->to('/#/login');
});

// Catch-all for 404
$routes->set404Override(function () {
    if (str_starts_with(uri_string(), 'api/')) {
        return service('response')
            ->setStatusCode(404)
            ->setJSON([
                'status'  => 'error',
                'message' => 'API endpoint tidak ditemukan',
            ]);
    }
    return view('spa', ['initialUser' => session()->get('isLoggedIn') ? [
        'id'       => (int) session()->get('user_id'),
        'username' => session()->get('username'),
        'email'    => session()->get('email'),
        'role'     => session()->get('role'),
    ] : null]);
});
