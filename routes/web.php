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

use Illuminate\Http\Request;

/**
 * @var $router Laravel\Lumen\Routing\Router;
 */
$router->get('/', function (Request $request) use ($router) {
    eval(\Psy\sh());
    return $router->app->version();
});

$router->post('/', function (Request $request) use ($router) {
//    eval(\Psy\sh());
    return $router->app->version();
});
