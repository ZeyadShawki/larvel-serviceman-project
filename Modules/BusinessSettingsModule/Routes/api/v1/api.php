<?php

use Illuminate\Http\Request;
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

Route::group(['prefix' => 'admin', 'as'=>'admin.', 'namespace' => 'Api\V1\Admin','middleware'=>['auth:api']], function () {
    Route::group(['prefix'=>'business-settings'],function (){
        Route::get('get-business-information', 'BusinessInformationController@business_information_get');
        Route::put('set-business-information', 'BusinessInformationController@business_information_set');

        Route::get('get-service-setup', 'BusinessInformationController@service_setup_get');
        Route::put('set-service-setup', 'BusinessInformationController@service_setup_set');

        Route::get('get-pages-setup', 'BusinessInformationController@pages_setup_get');
        Route::put('set-pages-setup', 'BusinessInformationController@pages_setup_set');

        Route::get('get-notification-setting', 'ConfigurationController@notification_settings_get');
        Route::put('set-notification-setting', 'ConfigurationController@notification_settings_set');

        Route::get('get-email-config', 'ConfigurationController@email_config_get');
        Route::put('set-email-config', 'ConfigurationController@email_config_set');

        Route::get('get-third-party-config', 'ConfigurationController@third_party_config_get');
        Route::put('set-third-party-config', 'ConfigurationController@third_party_config_set');
    });
});
