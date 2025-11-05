<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomTypeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;


    Route::prefix('auth')->group(function(){
        Route::post('/login',[AuthController::class,'login']);
        Route::post('/register',[AuthController::class,'register']);
        
    });

    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/profile',[AuthController::class,'profile']);
        Route::put('/profile',[AuthController::class,'updateProfile']);
        Route::post('/profile/avatar',[AuthController::class,'updateAvatar']);
        Route::post('/logout',[AuthController::class,'logout']);
    });
    //room
    use App\Http\Controllers\RoomController;
    Route::get('/rooms', [RoomController::class, 'index']);       
    Route::post('/rooms', [RoomController::class, 'store']);    
    Route::get('/rooms/{id}', [RoomController::class, 'show_by_id']); 
    Route::put('/rooms/{id}', [RoomController::class, 'update']); 
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
    //roomtype
    Route::get('/room-types',[RoomTypeController::class,'index']);
    Route::get('/room-types/{id}',[RoomTypeController::class,'show']);

    //room images
    use App\Http\Controllers\RoomImageController;
    Route::get('/room-images', [RoomImageController::class, 'index']);
    Route::get('/room-images/type/{typeId}', [RoomImageController::class, 'getByType']);
    Route::post('/room-images', [RoomImageController::class, 'store']);
    Route::delete('/room-images/{id}', [RoomImageController::class, 'destroy']);

    //dịch vụ
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

    // bookings (chỉ cho user đã đăng nhập)
    use App\Http\Controllers\BookingController;
    Route::middleware('auth:sanctum')->get('/bookings', [BookingController::class, 'index']);
?>