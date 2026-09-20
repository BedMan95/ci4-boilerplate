<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('api/inventory', ['filter' => 'apiAuth'], function ($routes) {
    $routes->get('/', '\App\Modules\Inventory\Controllers\InventoryController::index');
    $routes->post('/', '\App\Modules\Inventory\Controllers\InventoryController::store');
    $routes->get('(:num)', '\App\Modules\Inventory\Controllers\InventoryController::show/$1');
    $routes->put('(:num)', '\App\Modules\Inventory\Controllers\InventoryController::update/$1');
    $routes->delete('(:num)', '\App\Modules\Inventory\Controllers\InventoryController::delete/$1');
});