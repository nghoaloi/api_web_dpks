<?php

namespace App\Http\Controllers;

use App\Models\RoomTypeAmenity;
use Illuminate\Http\Request;

use App\Models\RoomType;
use App\Models\Amenity;
class RoomTypeAmenityController extends Controller
{
    /**
     * Thêm tiện nghi cho loại phòng
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_type_id' => 'required|integer|exists:room_types,id',
            'amenity_id'   => 'required|integer|exists:amenities,id',
        ]);

        // Tránh thêm trùng
        $item = RoomTypeAmenity::firstOrCreate([
            'room_type_id' => $validated['room_type_id'],
            'amenity_id'   => $validated['amenity_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm tiện nghi cho loại phòng thành công',
            'data'    => $item
        ], 201);
    }

    /**
     * Xoá tiện nghi khỏi loại phòng
     */
    public function destroy($id)
    {
        $item = RoomTypeAmenity::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy dữ liệu'
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoá tiện nghi khỏi loại phòng thành công'
        ]);
    }
    // lấy danh sách tiện ích của 1 loại phòng
    public function getAmenitiesOfRoomType($roomTypeId)
    {
        $roomType = RoomType::with('amenities')->findOrFail($roomTypeId);

       return response()->json([
            'success' => true,
            'all_amenities' => Amenity::all(),             
            'selected_ids' => $roomType->amenities
                ->pluck('id')
                ->values()                                  
        ]);
    }
    // cập nhật các tiệt ích cho loại phòng
    public function syncAmenities(Request $request)
    {
        $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'amenity_ids'  => 'array'
        ]);

        $roomType = RoomType::findOrFail($request->room_type_id);

        // sync = tự thêm + tự xoá
        $roomType->amenities()->sync($request->amenity_ids ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật tiện ích loại phòng thành công'
        ]);
    }
}
