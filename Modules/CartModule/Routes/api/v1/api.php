<?php

use Illuminate\Support\Facades\Route;
use Modules\CartModule\Http\Controllers\Api\V1\Customer\CartController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'customer', 'as' => 'customer.', 'namespace' => 'Api\V1\Customer', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix' => 'cart', 'as' => 'cart.',], function () {
        Route::post('add', 'CartController@add_to_cart');
        Route::get('list', 'CartController@list');
        Route::put('update-quantity/{id}', 'CartController@update_qty');
        Route::put('update/provider', [CartController::class, 'update_provider']);
        Route::delete('remove/{id}', 'CartController@remove');
        Route::delete('data/empty', 'CartController@empty_cart');
    });
});

