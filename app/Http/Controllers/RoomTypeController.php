<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class RoomTypeController extends Controller
{
    /**
     * Lấy danh sách loại phòng
     */
    public function index()
    {
        try {
            $roomtypes = RoomType::query()->latest()->get();
            return response()->json([
                'data' => $roomtypes
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi lấy danh sách loại phòng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thêm mới loại phòng
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'base_price' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'max_cap' => 'nullable|integer|min:1',
                'payment_type' => 'required|string',
                'allow_pet' => 'required|string',
                'single_bed' => 'required|integer|min:0',
                'double_bed' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $roomtype = RoomType::create($request->all());

            return response()->json([

                'message' => 'Thêm loại phòng thành công',
                'data' => $roomtype
            ], 201);
        } catch (Exception $e) {
            return response()->json([

                'message' => 'Lỗi khi thêm loại phòng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xem chi tiết 1 loại phòng theo id
     */
    public function show_by_id($id)
    {
        try {
            $roomtype = RoomType::find($id);

            if (!$roomtype) {
                return response()->json([
                    'message' => 'Không tìm thấy loại phòng'
                ], 404);
            }
            return response()->json([
                'data' => $roomtype
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi lấy chi tiết loại phòng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật loại phòng
     */
    public function update(Request $request, $id)
    {
        try {
            $roomtype = RoomType::find($id);
            if (!$roomtype) {
                return response()->json([
                    'message' => 'Không tìm thấy loại phòng'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'base_price' => 'sometimes|required|numeric|min:0',
                'description' => 'nullable|string',
                'max_cap' => 'nullable|integer|min:1',
                'payment_type' => 'sometimes|required|string',
                'allow_pet' => 'sometimes|required|string',
                'single_bed' => 'sometimes|required|integer|min:0',
                'double_bed' => 'sometimes|required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $roomtype->update($request->all());

            return response()->json([
                'message' => 'Cập nhật loại phòng thành công',
                'data' => $roomtype
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi cập nhật loại phòng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa loại phòng
     */
    public function destroy($id)
    {
        try {
            $roomtype = RoomType::find($id);

            if (!$roomtype) {
                return response()->json([

                    'message' => 'Không tìm thấy loại phòng'
                ], 404);
            }

            $roomtype->delete();

            return response()->json([

                'message' => 'Xóa loại phòng thành công'
            ], 200);
        } catch (Exception $e) {
            return response()->json([

                'message' => 'Lỗi khi xóa loại phòng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
