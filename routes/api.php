<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomTypeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TienichContronller;
use App\Http\Controllers\ReviewController;


    Route::prefix('auth')->group(function(){
        Route::post('/login',[AuthController::class,'login']);
        Route::post('/register',[AuthController::class,'register']);
        
    });

Route::get('/room-types/{id}/reviews', [ReviewController::class, 'index']);

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
    Route::get('/room-types/{id}/availability',[RoomTypeController::class,'checkAvailability']);


    //dịch vụ
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);


    // bookings 
    use App\Http\Controllers\BookingController;
    use App\Http\Controllers\PaymentController;
    use App\Http\Controllers\VNPayController;
Route::middleware('auth:sanctum')->group(function(){
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/{id}', [BookingController::class, 'show']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::post('/payments/update', [PaymentController::class, 'update']);
        Route::post('/room-types/{id}/reviews', [ReviewController::class, 'store']);
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
    });
    
    
    Route::get('/vnpay/return', [VNPayController::class, 'return']);
?>