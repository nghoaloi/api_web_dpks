<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    // Lấy tất cả các phòng
    public function index()
    {
        // Eager load (load kèm dữ liệu liên quan nếu muốn)
        $rooms = Room::all();
        return response()->json($rooms);
    }
    // Thêm 1 phòng mới
    public function store(Request $request)
    {
        // Validate dữ liệu gửi lên
        $validated = $request->validate([
            'room_type_id' => 'required|integer',
            'room_name'    => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric',
            'status'       => 'required|string|in:Còn phòng,Đã có người,Bảo trì',
        ]);

        // Tạo room
        $room = Room::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Room created successfully',
            'data'    => $room
        ], 201);
    }

}
