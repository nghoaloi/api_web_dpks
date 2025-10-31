<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\RoomTypeController;
// booking
Route::get('api/bookings', [BookingController::class, 'index']);

// room

//dịch vụ


//loại phòng
Route::get('api/room-types',[RoomTypeController::class,'index']);

// user
use App\Http\Controllers\UserController;

Route::get('api/users', [UserController::class, 'index']);


Route::get('/', function () {
    return view('welcome');
});
