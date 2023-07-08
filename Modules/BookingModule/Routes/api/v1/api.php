<?php

use Illuminate\Support\Facades\Route;

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
    Route::group(['prefix'=>'booking','as'=>'booking.'],function (){
        Route::get('/', 'BookingController@index');
        Route::get('/{booking_id}', 'BookingController@show');
        Route::post('request/send', 'BookingController@place_request')->middleware('hitLimiter');
        Route::put('status-update/{booking_id}', 'BookingController@status_update');
    });
});


Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix'=>'booking','as'=>'booking.'],function (){
        Route::post('/', 'BookingController@index');
        Route::get('{id}', 'BookingController@show');
        Route::put('status-update/{booking_id}', 'BookingController@status_update');
        Route::put('schedule-update/{booking_id}', 'BookingController@schedule_update');
        Route::get('data/download', 'BookingController@download');
    });
});


Route::group(['prefix' => 'provider', 'as' => 'provider.', 'namespace' => 'Api\V1\Provider', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix'=>'booking','as'=>'booking.'],function (){
        Route::post('/', 'BookingController@index');
        Route::get('{id}', 'BookingController@show');
        Route::put('request-accept/{booking_id}', 'BookingController@request_accept');
        Route::put('status-update/{booking_id}', 'BookingController@status_update');
        Route::put('schedule-update/{booking_id}', 'BookingController@schedule_update');
        Route::put('assign-serviceman/{booking_id}', 'BookingController@assign_serviceman');
        Route::get('data/download', 'BookingController@download');
    });
});


Route::group(['prefix' => 'serviceman', 'as' => 'serviceman.', 'namespace' => 'Api\V1\Serviceman', 'middleware' => ['auth:api']], function () {
    Route::group(['prefix'=>'booking','as'=>'booking.'],function (){
        Route::put('status-update/{booking_id}', 'BookingController@status_update');
        Route::put('payment-status-update/{booking_id}', 'BookingController@payment_status_update');
        Route::get('list', 'BookingController@booking_list');
        Route::get('detail/{id}', 'BookingController@booking_details');
    });
});
