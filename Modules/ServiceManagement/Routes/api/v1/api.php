<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\ServiceManagement\Http\Controllers\Api\V1\Customer\ServiceController as CustomerServiceController;
use Modules\ServiceManagement\Http\Controllers\Api\V1\Provider\ServiceRequestController;

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

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Api\V1\Admin', 'middleware' => ['auth:api']], function () {
    Route::resource('service', 'ServiceController', ['only' => ['index', 'store', 'edit', 'update', 'show']]);
    Route::put('service/status/update', 'ServiceController@status_update');
    Route::delete('service/delete', 'ServiceController@destroy');

    Route::resource('faq', 'FAQController', ['only' => ['index', 'store', 'edit', 'update', 'show']]);
    Route::put('faq/status/update', 'FAQController@status_update');
    Route::delete('faq/delete', 'FAQController@destroy');
});

Route::group(['prefix' => 'provider', 'as' => 'provider.', 'namespace' => 'Api\V1\Provider', 'middleware' => ['auth:api']], function () {
    Route::resource('service', 'ServiceController', ['only' => ['index', 'show']]);
    Route::put('service/status/update', 'ServiceController@status_update');
    Route::get('service/data/search', 'ServiceController@search');
    Route::get('service/review/{service_id}', 'ServiceController@review');

    Route::get('service-request', [ServiceRequestController::class, 'index']);
    Route::post('service-request', [ServiceRequestController::class, 'make_request']);

    Route::resource('faq', 'FAQController', ['only' => ['index', 'show']]);
});

Route::group(['prefix' => 'customer', 'as' => 'customer.', 'namespace' => 'Api\V1\Customer'], function () {
    Route::group(['prefix' => 'service'], function () {
        Route::get('/', [CustomerServiceController::class, 'index']);
        Route::get('search', [CustomerServiceController::class, 'search']);
        Route::get('search/recommended', [CustomerServiceController::class, 'search_recommended']);
        Route::get('popular', [CustomerServiceController::class, 'popular']);
        Route::get('recommended', [CustomerServiceController::class, 'recommended']);
        Route::get('trending', [CustomerServiceController::class, 'trending']);
        Route::get('recently-viewed', [CustomerServiceController::class, 'recently_viewed'])->middleware('auth:api');
        Route::get('offers', [CustomerServiceController::class, 'offers']);
        Route::get('detail/{id}', [CustomerServiceController::class, 'show']);
        Route::get('review/{service_id}', [CustomerServiceController::class, 'review']);
        Route::get('sub-category/{sub_category_id}', [CustomerServiceController::class, 'services_by_subcategory']);

        Route::group(['prefix' => 'request'], function () {
            Route::post('make', [CustomerServiceController::class, 'make_request'])->middleware('auth:api');
            Route::get('list', [CustomerServiceController::class, 'request_list'])->middleware('auth:api');
        });

    });
    Route::get('recently-searched-keywords', 'ServiceController@recently_searched_keywords')->middleware('auth:api');
    Route::get('remove-searched-keywords', 'ServiceController@remove_searched_keywords')->middleware('auth:api');
});
