<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Room;
use Exception;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $roomtype = RoomType::with('images')
                ->latest()
                ->get();
            return response()->json([
                'success' => true,
                'data' => $roomtype
            ], 200);
        }
        catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message'=>'Lỗi lấy danh sách loại phòng',
                'error'=>$e->getMessage()
            ],500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $roomType = RoomType::with('images')->find($id);
            
            if (!$roomType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy loại phòng'
                ], 404);
            }

            // Đếm số phòng trống của loại phòng này
            $availableRoomsCount = Room::where('type_id', $id)
                ->where('status', 'Còn phòng')
                ->count();
            
            // Tổng số phòng của loại này
            $totalRoomsCount = Room::where('type_id', $id)->count();

            // Thêm thông tin số phòng vào response
            $roomTypeData = $roomType->toArray();
            $roomTypeData['available_rooms_count'] = $availableRoomsCount;
            $roomTypeData['total_rooms_count'] = $totalRoomsCount;

            return response()->json([
                'success' => true,
                'data' => $roomTypeData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lấy thông tin loại phòng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
