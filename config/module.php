<?php
declare(strict_types=1);

use App\Controller\ExampleController;
use App\Controller\HelloWorldController;

/**
 * Application routes. The first match wins, so /items/create has to come
 * before /items/{id}. Add 'csrf' => false only for an endpoint that cannot
 * carry a token.
 */
return [
    'name'    => 'my-app',
    'version' => '1.0.0',
    'path'    => __DIR__,
    'routes'  => [
        ['method' => 'GET',  'path' => '/',                   'controller' => HelloWorldController::class, 'action' => 'index'],
        ['method' => 'GET',  'path' => '/items',              'controller' => ExampleController::class,    'action' => 'index'],
        ['method' => 'GET',  'path' => '/items/create',       'controller' => ExampleController::class,    'action' => 'create'],
        ['method' => 'POST', 'path' => '/items/create',       'controller' => ExampleController::class,    'action' => 'create'],
        ['method' => 'GET',  'path' => '/items/{id}',         'controller' => ExampleController::class,    'action' => 'show'],
        ['method' => 'POST', 'path' => '/items/{id}/delete',  'controller' => ExampleController::class,    'action' => 'delete'],
    ],
];
