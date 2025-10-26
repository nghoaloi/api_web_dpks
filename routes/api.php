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

    Route::get('/room-types',[RoomTypeController::class,'index']);
?>