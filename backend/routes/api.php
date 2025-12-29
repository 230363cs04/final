<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here we register endpoints to demonstrate overbooking (unsafe) and
| proper synchronization (safe), plus a status endpoint.
|
*/

Route::post('/unsafe-book', [BookingController::class, 'unsafeBook']);
Route::post('/safe-book', [BookingController::class, 'safeBook']);
Route::get('/status', [BookingController::class, 'status']);