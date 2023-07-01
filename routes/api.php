<?php

use App\Http\Controllers\Api\AvailabilityController;
use Illuminate\Http\Request;
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

Route::get('environments', [\App\Http\Controllers\Api\EnvironmentController::class, 'index'])
    ->name('environments.index');

Route::get('api-keys', [\App\Http\Controllers\Api\ApiKeyController::class, 'index'])
    ->name('api-keys.index');

Route::post('availability', [AvailabilityController::class, 'index'])
    ->name('availability');

Route::post('resources/{resource}/bookings', [\App\Http\Controllers\Api\BookingController::class, 'post'])
    ->name('bookings.create');

Route::delete('bookings/{resource}', [\App\Http\Controllers\Api\BookingController::class, 'destroy'])
    ->name('bookings.destroy');

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

