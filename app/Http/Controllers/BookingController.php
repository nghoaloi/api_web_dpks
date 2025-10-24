<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // Lấy toàn bộ danh sách booking
    public function index()
    {
       
        $bookings = Booking::all();

        return response()->json($bookings);
    }
}
