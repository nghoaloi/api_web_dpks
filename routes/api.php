<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomTypeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\BookingServiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;

    Route::prefix('auth')->group(function(){
        Route::post('/login',[AuthController::class,'login']);
        Route::post('/register',[AuthController::class,'register']);
        
    });

    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/profile',[AuthController::class,'profile']);
        Route::put('/profile',[AuthController::class,'updateProfile']);
        Route::post('/logout',[AuthController::class,'logout']);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/rooms', [RoomController::class, 'index']);       
    Route::post('/rooms', [RoomController::class, 'store']);    
    Route::get('/rooms/{id}', [RoomController::class, 'show_by_id']); 
    Route::put('/rooms/{id}', [RoomController::class, 'update']); 
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
    //roomtype
    Route::get('/room-types',[RoomTypeController::class,'index']);
    Route::post('/room-types', [RoomTypeController::class, 'store']);    
    Route::get('/room-types/{id}', [RoomTypeController::class, 'show_by_id']); 
    Route::put('/room-types/{id}', [RoomTypeController::class, 'update']); 
    Route::delete('/room-types/{id}', [RoomTypeController::class, 'destroy']);
    //dịch vụ
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
    // đặt dịch vụ


    Route::get('/booking-services', [BookingServiceController::class, 'index']);
    Route::get('/booking-services/{id}', [BookingServiceController::class, 'show']);
    Route::post('/booking-services', [BookingServiceController::class, 'store']);
    Route::put('/booking-services/{id}', [BookingServiceController::class, 'update']);
    Route::delete('/booking-services/{id}', [BookingServiceController::class, 'destroy']);
    // thanh toán


    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::put('/payments/{id}', [PaymentController::class, 'update']);
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
    // review 


    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::get('/reviews/{id}', [ReviewController::class, 'show']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

});

?>