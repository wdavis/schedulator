<?php

use App\Http\Controllers\Api\ScheduleOpeningsController;
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

Route::get('availability', [ScheduleOpeningsController::class, 'getAvailability'])
    ->name('availability');

Route::get('schedules/{resource}/openings', [ScheduleOpeningsController::class, 'getOpenings'])
    ->name('schedules.openings');

Route::get('schedules/{resource}/slots', [\App\Http\Controllers\Api\SlotController::class, 'index'])
    ->name('schedules.slots');

Route::post('bookings/{resource}', [\App\Http\Controllers\Api\BookingController::class, 'post'])
    ->name('bookings.create');

Route::put('resources/{resource}/schedule', [\App\Http\Controllers\Api\ScheduleController::class, 'update'])
    ->name('schedules.update');

Route::resource('resources', \App\Http\Controllers\Api\ResourceController::class)
    ->only(['index', 'show', 'store', 'update', 'destroy']);

Route::put('resources/{resource}/{toggle}', [\App\Http\Controllers\Api\ResourceToggleController::class, 'update'])
    ->name('resource-toggle.update')->where('toggle', 'active|inactive');

