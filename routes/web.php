<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\UserController;
// booking
// Route::get('api/bookings', [BookingController::class, 'index']);

// Route::get('api/users', [UserController::class, 'index']);


Route::get('/', function () {
    return view('welcome');
});
