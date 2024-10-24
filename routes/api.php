<?php

use App\Http\Controllers\Api\AvailabilityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// routes/api.php

Route::group(['middleware' => 'api-key:master'], function () {

    Route::get('test-master-api-access', function () {
        return response('OK', 200);
    })->name('test-master-api-access');

    Route::post('accounts', [\App\Http\Controllers\Api\Master\AccountCreationController::class, 'store'])
        ->name('accounts.store');

    Route::post('accounts/{userId}/environments', [\App\Http\Controllers\Api\Master\AccountEnvironmentCreationController::class, 'store'])
        ->name('accounts.environments.store');

    Route::get('users/{userId}/api-keys', [\App\Http\Controllers\Api\Master\UserApiKeyController::class, 'index'])
        ->name('users.api-keys.index');

    Route::delete('environments/{environmentId}/reset', [\App\Http\Controllers\Api\Master\AccountEnvironmentResetController::class, 'destroy'])
        ->name('environments.reset');
});

Route::group(['middleware' => 'api-key'], function () {

    Route::get('test-api-access', function () {
        return response('OK', 200);
    })->name('test-api-access');

    Route::get('environments', [\App\Http\Controllers\Api\EnvironmentController::class, 'index'])
        ->name('environments.index');

    Route::get('api-keys', [\App\Http\Controllers\Api\ApiKeyController::class, 'index'])
        ->name('api-keys.index');

    Route::post('availability', [AvailabilityController::class, 'index'])
        ->name('availability');

    Route::post('first-availability', [\App\Http\Controllers\Api\FirstAvailabilityController::class, 'index'])
        ->name('first-availability');

    Route::post('calendar', [\App\Http\Controllers\Api\CalendarController::class, 'index'])
        ->name('calendar.index');

    Route::get('resources/{resource}/bookings', [\App\Http\Controllers\Api\BookingController::class, 'index'])
        ->name('bookings.index');

    Route::post('resources/{resource}/bookings', [\App\Http\Controllers\Api\BookingController::class, 'post'])
        ->name('bookings.create');

    Route::delete('bookings/{resource}', [\App\Http\Controllers\Api\BookingController::class, 'destroy'])
        ->name('bookings.destroy');

    Route::put('bookings/{resource}', [\App\Http\Controllers\Api\BookingController::class, 'update'])
        ->name('bookings.update');

    Route::put('bookings/{resource}/cancel', [\App\Http\Controllers\Api\BookingController::class, 'update'])
        ->name('bookings-cancel.update');

    Route::delete('bookings/{resource}', [\App\Http\Controllers\Api\BookingController::class, 'destroy'])
        ->name('bookings.destroy');

    Route::post('bookings/{bookingId}/reschedule', [\App\Http\Controllers\Api\BookingRescheduleController::class, 'store'])
        ->name('bookings.reschedule.store');

    Route::post('bookings/external-lookup', [\App\Http\Controllers\Api\ExternalBookingController::class, 'index'])
        ->name('bookings.external-lookup.index');

    # Resource Schedule
    Route::get('resources/{resource}/schedule', [\App\Http\Controllers\Api\ScheduleController::class, 'index'])
        ->name('schedules.index');

    Route::put('resources/{resource}/schedule', [\App\Http\Controllers\Api\ScheduleController::class, 'update'])
        ->name('schedules.update');

    Route::resource('resources', \App\Http\Controllers\Api\ResourceController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);

    Route::put('resources/{resource}/{toggle}', [\App\Http\Controllers\Api\ResourceToggleController::class, 'update'])
        ->name('resource-toggle.update')->where('toggle', 'active|inactive');

    Route::resource('services', \App\Http\Controllers\Api\ServiceController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('date', [\App\Http\Controllers\Api\DateController::class, 'index'])
        ->name('date.index');

    Route::get('resources/{resource}/schedule-overrides', [\App\Http\Controllers\Api\ScheduleOverrideController::class, 'index'])
        ->name('schedule-overrides.index');
    Route::post('resources/{resource}/schedule-overrides', [\App\Http\Controllers\Api\ScheduleOverrideController::class, 'store'])
        ->name('schedule-overrides.store');
    Route::put('resources/{resource}/schedule-overrides/{month}', [\App\Http\Controllers\Api\ScheduleOverrideController::class, 'update'])
        ->name('schedule-overrides.update');
    Route::delete('resources/{resource}/schedule-overrides/{scheduleOverride}', [\App\Http\Controllers\Api\ScheduleOverrideController::class, 'destroy'])
        ->name('schedule-overrides.destroy');

    // reporting
    Route::post('reports/availability-count', [\App\Http\Controllers\Api\ForecastCountController::class, 'index'])
        ->name('reports.availability-count.index');

    Route::post('reports/bookings', [\App\Http\Controllers\Api\ForecastBookingsController::class, 'index'])
        ->name('reports.bookings.index');

    Route::post('reports/heat', [\App\Http\Controllers\Api\ForecastHeatmapController::class, 'index'])
        ->name('reports.heat.index');

//    Route::post('reports/bookings', [\App\Http\Controllers\Api\ForecastBookingLeadController::class, 'index'])
//        ->name('reports.bookings.index');



});
