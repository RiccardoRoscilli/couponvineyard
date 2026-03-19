<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\reservationController;
use App\Models\reservation;
use App\Http\Controllers\API\ActivityController;
use App\Http\Controllers\API\LocationController;

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


Route::middleware('auth.basic')->group(function () {
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('reservations/create-coupon', [ReservationController::class, 'apiStore']);
  //  Route::get('reservations/create-coupon', [ReservationController::class, 'apiStore']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('v1/experiences', reservationController::class . '@create');
