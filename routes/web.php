<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/**
 * @var $router Laravel\Lumen\Routing\Router;
 */
$router->get('/', function () use ($router) {
    return '...';
});

$router->group(['middleware' => 'validateTodoist'], function ($router) {
    $router->post('/', 'HabitController@handle');
});
