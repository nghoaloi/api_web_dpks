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
    // Lấy chi tiết 1 phòng theo id
    public function show_by_id($id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }
        return response()->json($room);
    }
    // Sửa thông tin phòng
    public function update(Request $request, $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        $validated = $request->validate([
            'room_type_id' => 'sometimes|integer',
            'room_name'    => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'sometimes|numeric',
            'status'       => 'sometimes|string|in:Còn phòng,Đã có người,Bảo trì',
        ]);

        $room->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Room updated successfully',
            'data'    => $room
        ]);
    }

    // Xoá phòng
    public function destroy($id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Room deleted successfully'
        ]);
    }
}
