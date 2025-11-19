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
    // Lấy 1 tiện ích theo ID
    public function show($id)
    {
        $amenity = Amenity::find($id);
        if (!$amenity) {
            return response()->json([
                'message' => 'Không tìm thấy tiện ích'
            ], 404);
        }
        return response()->json([
            'data' => $amenity
        ],200);
    }

    // Thêm tiện ích mới
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'icon' => 'nullable|string',
            'description' => 'nullable|string'
        ]);
        $amenity = Amenity::create([
            'name' => $request->name,
            'icon' => $request->icon,
            'description' => $request->description
        ]);
        return response()->json([
            'message' => 'Thêm tiện ích thành công',
            'data' => $amenity
        ],200);
    }

    // Cập nhật tiện ích
    public function update(Request $request, $id)
    {
        $amenity = Amenity::find($id);
        if (!$amenity) {
            return response()->json([
                'message' => 'Không tìm thấy tiện ích'
            ], 404);
        }
        $request->validate([
            'name' => 'required',
            'icon' => 'nullable|string',
            'description' => 'nullable|string'
        ]);
        $amenity->update([
            'name' => $request->name,
            'icon' => $request->icon,
            'description' => $request->description
        ]);
        return response()->json([
            'message' => 'Cập nhật tiện ích thành công',
            'data' => $amenity
        ],200);
    }
    // Xóa tiện ích
    public function destroy($id)
    {
        $amenity = Amenity::find($id);
        if (!$amenity) {
            return response()->json([
                'message' => 'Không tìm thấy tiện ích'
            ], 404);
        }
        $amenity->delete();
        return response()->json([
            'message' => 'Xóa tiện ích thành công'
        ],200);
    }

    // Tìm kiếm tiện ích theo tên hoặc ID
    public function search(Request $request)
    {
        $name = $request->name;
        $id = $request->id;
        $query = Amenity::query();
        if ($name) {
            $query->where('name', 'LIKE', '%'.$name.'%');
        }
        if ($id) {
            $query->where('id', $id);
        }
        $amenities = $query->get();
        return response()->json([
            'data' => $amenities
        ],200);
    }
}