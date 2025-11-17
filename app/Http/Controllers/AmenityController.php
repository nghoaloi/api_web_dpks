<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    function index (){
        $amenity = Amenity::all();
        return response()->json($amenity);
    }

}