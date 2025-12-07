<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{


    //  Lấy danh sách tất cả phòng
    public function index()
    {
        // load thêm loại phòng để xem thông tin chi tiết
        $rooms = Room::with('roomType')->get();
        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

    //  Thêm 1 phòng mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_id'      => 'required|integer|exists:room_types,id',
            'room_number'  => 'required|string|max:255',
            'status'       => 'required|string|in:Còn phòng,Đã có người,Bảo trì',
        ]);

        $room = Room::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Phòng đã được thêm thành công!',
            'data'    => $room
        ], 201);
    }

    //  Lấy chi tiết 1 phòng theo ID
    // public function show_by_id($id)
    // {
    //     $room = Room::with('roomType')->find($id);

    //     if (!$room) {
    //         return response()->json(['message' => 'Không tìm thấy phòng'], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data' => $room
    //     ]);
    // }
    public function show_by_id($id)
    {
        $room = Room::with('roomtype')->find($id);

        if (!$room) {
            return response()->json(['message' => 'Không tìm thấy phòng'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $room
        ]);
    }

    //  Cập nhật thông tin phòng
    public function update(Request $request, $id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['message' => 'Không tìm thấy phòng'], 404);
        }

        $validated = $request->validate([
            'type_id'      => 'sometimes|integer|exists:room_types,id',
            'room_number'  => 'sometimes|string|max:255',
            'status'       => 'sometimes|string|in:Còn phòng,Đã có người,Bảo trì',
        ]);

        $room->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Phòng đã được cập nhật!',
            'data'    => $room
        ]);
    }

    //  Xóa phòng
    public function destroy($id)
    {
        $room = Room::find($id);
        if (!$room) {
            return response()->json(['message' => 'Không tìm thấy phòng'], 404);
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Phòng đã được xóa!'
        ]);
    }
    // tìm theo số phòng với mã phòng 
    public function search(Request $request)
    {

        $room_number = $request->input('room_number');
        $id = $request->input('id');

        $query = Room::query();

        // Tìm theo ID nếu có
        if (!empty($id)) {
            $query->where('id', $id);
        }

        // Tìm theo số phòng nếu có
        if (!empty($room_number)) {
            $query->where('room_number', 'LIKE', '%' . $room_number . '%');
        }

        // Load quan hệ roomType
        $rooms = $query->get();

        return response()->json([
            'success' => true,
            'data' => $rooms
        ]);
    }

}