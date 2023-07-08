<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomerModule\Http\Controllers\Api\V1\Customer\CustomerController;

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

//admin section
Route::group(['prefix' => 'admin', 'as'=>'admin.', 'namespace' => 'Api\V1\Admin','middleware'=>['auth:api']], function () {
    Route::resource('customer', 'CustomerController', ['only' => ['index', 'store', 'edit', 'update']]);
    Route::group(['prefix' => 'customer', 'as' => 'customer.',], function () {
        Route::put('status/update', 'CustomerController@status_update');
        Route::delete('delete', 'CustomerController@destroy');

        Route::group(['prefix' => 'data', 'as' => 'data.',], function () {
            Route::get('overview/{id}', 'CustomerController@overview');
            Route::get('bookings/{id}', 'CustomerController@bookings');
            Route::get('reviews/{id}', 'CustomerController@reviews');

            Route::post('store-address', 'CustomerController@store_address');
            Route::get('edit-address/{id}', 'CustomerController@edit_address');
            Route::put('update-address/{id}', 'CustomerController@update_address');
            Route::delete('delete/{id}', 'CustomerController@destroy_address');
        });

    });
});

//customer section
Route::group(['prefix' => 'customer', 'as' => 'customer.', 'namespace' => 'Api\V1\Customer'], function () {

    Route::post('forgot-password', 'CustomerController@forgot_password');
    Route::post('otp-verification', 'CustomerController@otp_verification');
    Route::put('reset-password', 'CustomerController@reset_password');

    Route::group(['prefix' => 'config'], function () {
        Route::get('/', 'ConfigController@configuration');
        Route::get('pages', 'ConfigController@pages');
        Route::get('get-zone-id', 'ConfigController@get_zone');
        Route::get('place-api-autocomplete', 'ConfigController@place_api_autocomplete');
        Route::get('distance-api', 'ConfigController@distance_api');
        Route::get('place-api-details', 'ConfigController@place_api_details');
        Route::get('geocode-api', 'ConfigController@geocode_api');
    });

    Route::group(['middleware' => ['auth:api']], function () {
        Route::put('update/fcm-token','CustomerController@update_fcm_token');
        Route::put('update/profile','CustomerController@update_profile');
        Route::get('info', 'CustomerController@index');
        Route::delete('remove-account', 'CustomerController@remove_account');
        Route::resource('address', 'AddressController', ['only' => ['index', 'store', 'edit', 'update']]);
        Route::delete('address', 'AddressController@destroy');

        Route::post('loyalty-point/wallet-transfer', [CustomerController::class, 'transfer_loyalty_point_to_wallet']);
        Route::get('wallet-transaction', [CustomerController::class, 'wallet_transaction']);
        Route::get('loyalty-point-transaction', [CustomerController::class, 'loyalty_point_transaction']);
    });
});

