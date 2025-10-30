<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomTypeController;
use Illuminate\Support\Facades\Route;


    Route::prefix('auth')->group(function(){
        Route::post('/login',[AuthController::class,'login']);
        Route::post('/register',[AuthController::class,'register']);
        
    });

    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/profile',[AuthController::class,'profile']);
        Route::put('/profile',[AuthController::class,'updateProfile']);
        Route::post('/logout',[AuthController::class,'logout']);
    });
    //room
    use App\Http\Controllers\RoomController;
    Route::get('/rooms', [RoomController::class, 'index']);       // Lấy danh sách
    Route::post('/rooms', [RoomController::class, 'store']);      // Thêm phòng
    Route::get('/rooms/{id}', [RoomController::class, 'show_by_id']);   // Chi tiết phòng
    Route::put('/rooms/{id}', [RoomController::class, 'update']); // Sửa phòng
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']); // Xoá phòng
    //roomtype
    Route::get('/room-types',[RoomTypeController::class,'index']);
?>