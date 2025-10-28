<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ServiceController;

// booking
Route::get('api/bookings', [BookingController::class, 'index']);

// room
Route::get('api/rooms', [RoomController::class, 'index']);       // Lấy danh sách
Route::post('api/rooms', [RoomController::class, 'store']);      // Thêm phòng
Route::get('api/rooms/{id}', [RoomController::class, 'show_by_id']);   // Chi tiết phòng
Route::put('api/rooms/{id}', [RoomController::class, 'update']); // Sửa phòng
Route::delete('api/rooms/{id}', [RoomController::class, 'destroy']); // Xoá phòng
//dịch vụ

Route::get('api/services', [ServiceController::class, 'index']);
//loại phòng


// user
use App\Http\Controllers\UserController;

Route::get('api/users', [UserController::class, 'index']);


Route::get('/', function () {
    return view('welcome');
});
