<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoomTypeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\BookingAdminController;
use App\Http\Controllers\RoomTypeAdminController;
use App\Http\Controllers\AmenityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BookingServiceAdminController;
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
    Route::get('/room-types/{id}/availability',[RoomTypeController::class,'checkAvailability']);
    // bookings (chỉ cho user đã đăng nhập)
    use App\Http\Controllers\BookingController;
    use App\Http\Controllers\PaymentController;
    use App\Http\Controllers\VNPayController;
    Route::middleware('auth:sanctum')->group(function(){
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/{id}', [BookingController::class, 'show']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::post('/payments/update', [PaymentController::class, 'update']);
        Route::post('/vnpay/create-payment-url', [VNPayController::class, 'createPaymentUrl']);
    });
    
    
    Route::get('/vnpay/return', [VNPayController::class, 'return']);
    Route::post('/vnpay/ipn', [VNPayController::class, 'ipn']); 

    //api admin

    
    Route::middleware('auth:sanctum')->group(function(){
        // tiện ích
        Route::get('/amenity', [AmenityController::class, 'index']);
        Route::get('/amenity/{id}', [AmenityController::class, 'show']);
        Route::post('/amenity', [AmenityController::class, 'store']);
        Route::put('/amenity/{id}', [AmenityController::class, 'update']);
        Route::delete('/amenity/{id}', [AmenityController::class, 'destroy']);
        
        Route::get('/amenity-search', [AmenityController::class, 'search']);
        // đặt dịch vụ
        // Route::get('booking-services/', [BookingServiceAdminController::class, 'index']);
        Route::get('booking-services/{id}', [BookingServiceAdminController::class, 'show']);
        Route::post('booking-services/', [BookingServiceAdminController::class, 'store']);
        Route::put('booking-services/{id}', [BookingServiceAdminController::class, 'update']);
        Route::delete('booking-services/{id}', [BookingServiceAdminController::class, 'destroy']);
        // laoị phòng
        Route::get('room-types/', [RoomTypeAdminController::class, 'index']);              
        Route::get('room-types/{id}', [RoomTypeAdminController::class, 'show_by_id']);      
        Route::post('room-types/', [RoomTypeAdminController::class, 'store']);              
        Route::put('room-types/{id}', [RoomTypeAdminController::class, 'update']);          
        Route::delete('room-types/{id}', [RoomTypeAdminController::class, 'destroy']);      
        // đặt phòng
        Route::get('/bookings', [BookingAdminController::class, 'index']);
        Route::get('/bookings/{id}', [BookingAdminController::class, 'show']);
        Route::post('/bookings', [BookingAdminController::class, 'store']);
        Route::put('/bookings/{id}', [BookingAdminController::class, 'update']);
        Route::delete('/bookings/{id}', [BookingAdminController::class, 'destroy']);
        // phòng
        Route::get('/rooms', [RoomController::class, 'index']);       
        Route::post('/rooms', [RoomController::class, 'store']);    
        Route::put('/rooms/{id}', [RoomController::class, 'update']); 
        Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
        Route::get('/room/search', [RoomController::class, 'search']);
        // dịch vụ
        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/services/{id}', [ServiceController::class, 'show']);
        Route::post('/services', [ServiceController::class, 'store']);
        Route::put('/services/{id}', [ServiceController::class, 'update']);
        Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
        // user
        Route::get('/users', [UserController::class, 'index']);
        Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::get('/users/search', [UserController::class, 'search']);

        //
    });
    Route::get('/bookings', [BookingAdminController::class, 'index']);
    // Route::get('/services', [ServiceController::class, 'index']);
    Route::get('booking-services/', [BookingServiceAdminController::class, 'index']);
        Route::get('/rooms/{id}', [RoomController::class, 'show_by_id']); 



?>