<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    //    Lấy danh sách tất cả review
    public function index()
    {
        $reviews = Review::with(['user', 'room'])->get();

        return response()->json([
              
            'data' => $reviews
        ]);
    }

    //    Lấy chi tiết review theo id
    public function show($id)
    {
        $review = Review::with(['user', 'room'])->find($id);

        if (!$review) {
            return response()->json([
                  
                'message' => 'Không tìm thấy đánh giá'
            ], 404);
        }

        return response()->json([
              
            'data' => $review
        ]);
    }

    //    Thêm mới review
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = Review::create($validated);

        return response()->json([
              
            'message' => 'Thêm đánh giá thành công!',
            'data' => $review
        ], 201);
    }

    //    Cập nhật review
    public function update(Request $request, $id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                  
                'message' => 'Không tìm thấy đánh giá'
            ], 404);
        }

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string|max:1000',
        ]);

        $review->update($validated);

        return response()->json([
              
            'message' => 'Cập nhật đánh giá thành công!',
            'data' => $review
        ]);
    }

    //    Xóa review
    public function destroy($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                  
                'message' => 'Không tìm thấy đánh giá'
            ], 404);
        }

        $review->delete();

        return response()->json([
              
            'message' => 'Xóa đánh giá thành công!'
        ]);
    }
}
