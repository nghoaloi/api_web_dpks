<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Room;
use App\Models\Review;
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
            $roomtypes = RoomType::with('images')->latest()->get();
            
            // Thêm thông tin rating cho mỗi room type
            $roomtypes->transform(function($roomType) {
                $roomTypeData = $roomType->toArray();
                
                // Tính average rating và số lượng reviews
                $reviews = Review::where('type_id', $roomType->id)->get();
                $reviewCount = $reviews->count();
                $avgRating = $reviewCount > 0 ? $reviews->avg('rating') : 0;
                
                $roomTypeData['rating'] = round($avgRating, 1);
                $roomTypeData['review_count'] = $reviewCount;
                
                return $roomTypeData;
            });
            
            return response()->json([
                'success' => true,
                'data' => $roomtypes
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

            // Tính average rating và số lượng reviews
            $reviews = Review::where('type_id', $id)->get();
            $reviewCount = $reviews->count();
            $avgRating = $reviewCount > 0 ? $reviews->avg('rating') : 0;

            // Thêm thông tin vào response
            $roomTypeData = $roomType->toArray();
            $roomTypeData['available_rooms_count'] = $availableRoomsCount;
            $roomTypeData['total_rooms_count'] = $totalRoomsCount;
            $roomTypeData['rating'] = round($avgRating, 1);
            $roomTypeData['review_count'] = $reviewCount;

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

    public function checkAvailability(Request $request, string $id)
    {
        try {
            $roomType = RoomType::find($id);
            if (!$roomType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy loại phòng'
                ], 404);
            }

            $request->validate([
                'check_in' => 'required|date',
                'check_out' => 'required|date|after:check_in',
                'quantity' => 'nullable|integer|min:1|max:10',
            ]);

            $checkIn = $request->input('check_in');
            $checkOut = $request->input('check_out');
            $requestedQuantity = $request->input('quantity', 1);

            $availableRoomsCount = Room::where('type_id', $id)
                ->where('status', 'Còn phòng')
                ->count();
                
            $totalRoomsCount = Room::where('type_id', $id)->count();

            $isAvailable = $availableRoomsCount >= $requestedQuantity;

            return response()->json([
                'success' => true,  
                'room_type_id' => (int)$id,
                'room_type_name' => $roomType->name,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'requested_quantity' => $requestedQuantity,
                'available_count' => $availableRoomsCount,
                'total_count' => $totalRoomsCount,
                'is_available' => $isAvailable,
                'message' => $isAvailable 
                    ? "Còn {$availableRoomsCount} phòng trống" 
                    : "Chỉ còn {$availableRoomsCount} phòng trống (yêu cầu: {$requestedQuantity})"
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kiểm tra phòng trống',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
// <?php

// namespace App\Http\Controllers;

// use App\Models\RoomType;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Validator;
// use Exception;

// class RoomTypeController extends Controller
// {
//     /**
//      * Lấy danh sách loại phòng
//      */
//     public function index()
//     {
//         try {
//             $roomtypes = RoomType::query()->latest()->get();
//             return response()->json([
//                 'data' => $roomtypes
//             ], 200);
//         } catch (Exception $e) {
//             return response()->json([
//                 'message' => 'Lỗi khi lấy danh sách loại phòng',
//                 'error' => $e->getMessage()
//             ], 500);
//         }
//     }

//     /**
//      * Thêm mới loại phòng
//      */
//     public function store(Request $request)
//     {
//         try {
//             $validator = Validator::make($request->all(), [
//                 'name' => 'required|string|max:255',
//                 'base_price' => 'required|numeric|min:0',
//                 'description' => 'nullable|string',
//                 'max_cap' => 'nullable|integer|min:1',
//                 'payment_type' => 'required|string',
//                 'allow_pet' => 'required|string',
//                 'single_bed' => 'required|integer|min:0',
//                 'double_bed' => 'required|integer|min:0',
//             ]);

//             if ($validator->fails()) {
//                 return response()->json([
//                     'message' => 'Dữ liệu không hợp lệ',
//                     'errors' => $validator->errors()
//                 ], 422);
//             }

//             $roomtype = RoomType::create($request->all());

//             return response()->json([

//                 'message' => 'Thêm loại phòng thành công',
//                 'data' => $roomtype
//             ], 201);
//         } catch (Exception $e) {
//             return response()->json([

//                 'message' => 'Lỗi khi thêm loại phòng',
//                 'error' => $e->getMessage()
//             ], 500);
//         }
//     }

//     /**
//      * Xem chi tiết 1 loại phòng theo id
//      */
//     public function show_by_id($id)
//     {
//         try {
//             $roomtype = RoomType::find($id);

//             if (!$roomtype) {
//                 return response()->json([
//                     'message' => 'Không tìm thấy loại phòng'
//                 ], 404);
//             }
//             return response()->json([
//                 'data' => $roomtype
//             ], 200);
//         } catch (Exception $e) {
//             return response()->json([
//                 'message' => 'Lỗi khi lấy chi tiết loại phòng',
//                 'error' => $e->getMessage()
//             ], 500);
//         }
//     }

//     /**
//      * Cập nhật loại phòng
//      */
//     public function update(Request $request, $id)
//     {
//         try {
//             $roomtype = RoomType::find($id);
//             if (!$roomtype) {
//                 return response()->json([
//                     'message' => 'Không tìm thấy loại phòng'
//                 ], 404);
//             }

//             $validator = Validator::make($request->all(), [
//                 'name' => 'sometimes|required|string|max:255',
//                 'base_price' => 'sometimes|required|numeric|min:0',
//                 'description' => 'nullable|string',
//                 'max_cap' => 'nullable|integer|min:1',
//                 'payment_type' => 'sometimes|required|string',
//                 'allow_pet' => 'sometimes|required|string',
//                 'single_bed' => 'sometimes|required|integer|min:0',
//                 'double_bed' => 'sometimes|required|integer|min:0',
//             ]);

//             if ($validator->fails()) {
//                 return response()->json([
//                     'message' => 'Dữ liệu không hợp lệ',
//                     'errors' => $validator->errors()
//                 ], 422);
//             }

//             $roomtype->update($request->all());

//             return response()->json([
//                 'message' => 'Cập nhật loại phòng thành công',
//                 'data' => $roomtype
//             ], 200);
//         } catch (Exception $e) {
//             return response()->json([
//                 'message' => 'Lỗi khi cập nhật loại phòng',
//                 'error' => $e->getMessage()
//             ], 500);
//         }
//     }

//     /**
//      * Xóa loại phòng
//      */
//     public function destroy($id)
//     {
//         try {
//             $roomtype = RoomType::find($id);

//             if (!$roomtype) {
//                 return response()->json([

//                     'message' => 'Không tìm thấy loại phòng'
//                 ], 404);
//             }

//             $roomtype->delete();

//             return response()->json([

//                 'message' => 'Xóa loại phòng thành công'
//             ], 200);
//         } catch (Exception $e) {
//             return response()->json([

//                 'message' => 'Lỗi khi xóa loại phòng',
//                 'error' => $e->getMessage()
//             ], 500);
//         }
//     }
// }
