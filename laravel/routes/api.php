<?php

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
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\PassengerController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/reservations', [ReservationController::class,'store']);
Route::patch('/reservations/{id}/status', [ReservationController::class,'updateStatus']);
Route::get('/reservations', [ReservationController::class,'index']);

Route::get('/passengers/{id}', [PassengerController::class,'show']);
