<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;

// booking
Route::get('api/bookings', [BookingController::class, 'index']);

// room
Route::get('api/rooms', [RoomController::class, 'index']);

Route::get('/', function () {
    return view('welcome');
});
