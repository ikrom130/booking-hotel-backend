<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReservationController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// public Auth rutes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

/*
|--------------------------------------------------------------------------
|  ADMIN ONLY
|--------------------------------------------------------------------------
| Admin dapat kelola user/staff dan semua booking
| (route controller UserController nanti kita buat)
*/
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // untuk manajemen akun (staff & user)
    // Route::apiResource('/users', UserController::class); // opsional, nanti
    Route::get('/reservations/all', [ReservationController::class, 'all']); // lihat semua booking
});

/*
|--------------------------------------------------------------------------
|  ADMIN + STAFF
|--------------------------------------------------------------------------
| Dapat kelola kamar dan reservasi
*/
Route::middleware(['auth:api', 'role:admin,staff'])->group(function () {
    Route::apiResource('/rooms', RoomController::class);
    Route::apiResource('/reservations', ReservationController::class)
        ->only(['index', 'store', 'update', 'destroy']);
});

/*
|--------------------------------------------------------------------------
|  USER
|--------------------------------------------------------------------------
| Dapat booking dan melihat booking miliknya sendiri
*/
Route::middleware(['auth:api', 'role:user'])->group(function () {
    Route::post('/book', [ReservationController::class, 'store']); // booking kamar
    Route::get('/my-bookings', [ReservationController::class, 'index']); // hanya booking miliknya
});
