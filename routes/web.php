<?php

use Illuminate\Support\Facades\Route;
// API routes should be in routes/api.php


Route::get('/', function () {
    return view('welcome');
});
