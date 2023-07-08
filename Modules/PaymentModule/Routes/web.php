<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentModule\Http\Controllers\FlutterwaveController;
use Modules\PaymentModule\Http\Controllers\PaystackController;
use Modules\PaymentModule\Http\Controllers\PaytmController;
use Modules\PaymentModule\Http\Controllers\RazorPayController;
use Modules\PaymentModule\Http\Controllers\SenangPayController;
use Modules\PaymentModule\Http\Controllers\SslCommerzPaymentController;
use Modules\PaymentModule\Http\Controllers\StripePaymentController;
use Modules\PaymentModule\Http\Controllers\Web\Admin\PaymentConfigController;

/** Payment */
Route::group(['prefix' => 'payment', 'middleware' => ['detectUser']], function () {

    /** SSLCommerz */
    Route::group(['prefix' => 'sslcommerz', 'as' => 'sslcommerz.'], function () {
        Route::get('pay', [SslCommerzPaymentController::class, 'index']);
        Route::post('success', [SslCommerzPaymentController::class, 'success']);
        Route::post('failed', [SslCommerzPaymentController::class, 'failed']);
        Route::post('canceled', [SslCommerzPaymentController::class, 'canceled']);
    });

    /** Stripe */
    Route::group(['prefix' => 'stripe', 'as' => 'stripe.'], function () {
        Route::get('pay', [StripePaymentController::class, 'index']);
        Route::get('token', [StripePaymentController::class, 'payment_process_3d'])->name('token')->WithoutMiddleware('detectUser');
        Route::any('success', [StripePaymentController::class, 'success'])->name('success')->WithoutMiddleware('detectUser');
        Route::any('cancel', [StripePaymentController::class, 'cancel'])->name('cancel')->WithoutMiddleware('detectUser');
    });

    /** Razorpay */
    Route::group(['prefix' => 'razor-pay', 'as' => 'razor-pay.'], function () {
        Route::get('pay', [RazorPayController::class, 'index']);
        Route::post('payment', [RazorPayController::class, 'payment'])->name('payment')->WithoutMiddleware('detectUser');
    });

    /** Paypal */
    /*Route::group(['prefix'=>'paypal','as'=>'paypal.'], function () {
        Route::get('pay','PaypalPaymentController@index');
        Route::any('callback','StripePaymentController@callback')->name('callback');
        Route::any('failed','StripePaymentController@failed')->name('failed');
    });*/

    /** Senang Pay */
    Route::group(['prefix' => 'senang-pay', 'as' => 'senang-pay.'], function () {
        Route::get('pay', [SenangPayController::class, 'index']);
        Route::get('callback', [SenangPayController::class, 'return_senang_pay'])->name('callback')->WithoutMiddleware('detectUser');
        //
    });

    /** Paytm */
    Route::group(['prefix' => 'paytm', 'as' => 'paytm.'], function () {
        Route::get('pay', [PaytmController::class, 'payment']);
        Route::any('response', [PaytmController::class, 'callback'])->name('response')->WithoutMiddleware('detectUser');
    });

    /** Flutterwave */
    Route::group(['prefix' => 'flutterwave', 'as' => 'flutterwave.'], function () {
        Route::get('pay', [FlutterwaveController::class, 'initialize'])->name('pay');
        Route::get('callback', [FlutterwaveController::class, 'callback'])->name('callback')->WithoutMiddleware('detectUser');
    });

    /** Paystack */
    Route::group(['prefix' => 'paystack', 'as' => 'paystack.'], function () {
        Route::get('pay', [PaystackController::class, 'index'])->name('pay');
        Route::post('payment', [PaystackController::class, 'redirectToGateway'])->name('payment')->WithoutMiddleware('detectUser');
        Route::get('callback', [PaystackController::class, 'handleGatewayCallback'])->name('callback');
    });

});

/** Admin */
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin', 'mpc:system_management']], function () {
    Route::group(['prefix' => 'configuration', 'as' => 'configuration.'], function () {
        Route::get('payment-get', [PaymentConfigController::class, 'payment_config_get'])->name('payment-get');
        Route::put('payment-set', [PaymentConfigController::class, 'payment_config_set'])->name('payment-set');
    });
});
