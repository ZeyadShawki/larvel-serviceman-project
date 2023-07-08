<?php

use Illuminate\Support\Facades\Route;
use Modules\BookingModule\Http\Controllers\Web\Admin\BookingController;
use Modules\BookingModule\Http\Controllers\Web\Provider\BookingController as ProviderBookingController;

Route::get('invoice', function () {
    $booking = \Modules\BookingModule\Entities\Booking::first();
    return view('bookingmodule::mail-templates.booking-request-sent', compact('booking'));
});

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Web\Admin', 'middleware' => ['admin', 'mpc:booking_management']], function () {

    Route::group(['prefix' => 'booking', 'as' => 'booking.'], function () {
        Route::any('list', [BookingController::class, 'index'])->name('list');
        Route::any('list/custom', [BookingController::class, 'custom_index'])->name('list.custom');
        Route::get('check', [BookingController::class, 'check_booking'])->name('check');
        Route::get('details/{id}', [BookingController::class, 'details'])->name('details');
        Route::get('status-update/{id}', [BookingController::class, 'status_update'])->name('status_update');
        Route::get('payment-update/{id}', [BookingController::class, 'payment_update'])->name('payment_update');
        Route::any('schedule-update/{id}', [BookingController::class, 'schedule_upadte'])->name('schedule_update');
        Route::get('serviceman-update/{id}', [BookingController::class, 'serviceman_update'])->name('serviceman_update');
        Route::post('service-address-update/{id}', [BookingController::class, 'service_address_update'])->name('service_address_update');
        Route::any('download', [BookingController::class, 'download'])->name('download');
        Route::any('invoice/{id}', [BookingController::class, 'invoice'])->name('invoice');

        Route::group(['prefix' => 'service', 'as' => 'service.'], function () {
            Route::put('add', [BookingController::class, 'add_booking_service'])->name('add');
            Route::put('remove', [BookingController::class, 'remove_booking_service'])->name('remove');

        });
        //ajax routes
        Route::get('service/ajax-get-variation', [BookingController::class, 'ajax_get_variant'])->name('service.ajax-get-variant');
    });
});

Route::group(['prefix' => 'provider', 'as' => 'provider.', 'namespace' => 'Web\Provider', 'middleware' => ['provider']], function () {

    Route::group(['prefix' => 'booking', 'as' => 'booking.'], function () {
        Route::any('list', [ProviderBookingController::class, 'index'])->name('list');
        Route::get('check', [ProviderBookingController::class, 'check_booking'])->name('check');
        Route::get('details/{id}', [ProviderBookingController::class, 'details'])->name('details');
        Route::get('status-update/{id}', [ProviderBookingController::class, 'status_update'])->name('status_update');
        Route::get('payment-update/{id}', [ProviderBookingController::class, 'payment_update'])->name('payment_update');
        Route::any('schedule-update/{id}', [ProviderBookingController::class, 'schedule_upadte'])->name('schedule_update');
        Route::get('serviceman-update/{id}', [ProviderBookingController::class, 'serviceman_update'])->name('serviceman_update');
        Route::get('service-address-update/{id}', [BookingController::class, 'service_address_update'])->name('service_address_update');
        Route::any('download', [ProviderBookingController::class, 'download'])->name('download');
        Route::any('invoice/{id}', [ProviderBookingController::class, 'invoice'])->name('invoice');
    });
});
