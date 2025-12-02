<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReservationController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/payment/callback', [ReservationController::class, 'callback']);


Route::middleware(['auth:api', 'jwt.refresh'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // universal untuk semua role
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
});

/*
|--------------------------------------------------------------------------
| ADMIN ONLY
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'jwt.refresh', 'role:admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/staff', [UserController::class, 'storeStaff']);
    Route::put('/admin/user/{id}', [UserController::class, 'update']);
    Route::delete('/admin/user/{id}', [UserController::class, 'destroy']);
    Route::get('/reservations/all', [ReservationController::class, 'all']);
});

/*
|--------------------------------------------------------------------------
| ADMIN + STAFF
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'jwt.refresh', 'role:admin,staff'])->group(function () {
    Route::apiResource('/rooms', RoomController::class);
    Route::get('/bookings', [ReservationController::class, 'index']);
    Route::put('/booking/status/{id}', [ReservationController::class, 'updateStatus']);
});

/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'jwt.refresh', 'role:user'])->group(function () {
    Route::post('/book', [ReservationController::class, 'store']);
    Route::get('/my-bookings', [ReservationController::class, 'myBooking']);
});
