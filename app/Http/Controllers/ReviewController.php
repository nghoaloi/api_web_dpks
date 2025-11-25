<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Models\RoomType;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, int $roomTypeId)
    {
        $roomType = RoomType::find($roomTypeId);
        if (!$roomType) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy loại phòng'
            ], 404);
        }

        $reviews = Review::where('type_id', $roomTypeId)
            ->with(['user:id,fullname,avatar'])
            ->orderByDesc('created_at')
            ->get();

        $average = round($reviews->avg('rating') ?? 0, 1);
        $count = $reviews->count();
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $reviews->where('rating', $i)->count();
        }

        $user = $request->user('sanctum');
        $userReview = null;
        $canReview = false;

        if ($user) {
            $userReview = $reviews->firstWhere('user_id', $user->id);
            $canReview = Booking::where('user_id', $user->id)
                ->where('status', 'Đã thanh toán')
                ->whereHas('room', function ($query) use ($roomTypeId) {
                    $query->where('type_id', $roomTypeId);
                })
                ->exists();
        }

        return response()->json([
            'success' => true,
            'data' => $reviews,
            'summary' => [
                'average' => $average,
                'count' => $count,
                'distribution' => $distribution,
            ],
            'user_review' => $userReview,
            'can_review' => $canReview,
        ]);
    }

    public function store(Request $request, int $roomTypeId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $roomType = RoomType::find($roomTypeId);
        if (!$roomType) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy loại phòng'
            ], 404);
        }

        $hasCompletedBooking = Booking::where('user_id', $user->id)
            ->where('status', 'Đã thanh toán')
            ->whereHas('room', function ($query) use ($roomTypeId) {
                $query->where('type_id', $roomTypeId);
            })
            ->exists();

        if (!$hasCompletedBooking) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần hoàn tất lưu trú trước khi đánh giá phòng này'
            ], 403);
        }

        $review = Review::updateOrCreate(
            ['user_id' => $user->id, 'type_id' => $roomTypeId],
            [
                'rating' => $request->input('rating'),
                'comment' => $request->input('comment'),
            ]
        );

        $review->load(['user:id,fullname,avatar']);

        return response()->json([
            'success' => true,
            'message' => 'Đánh giá đã được lưu',
            'data' => $review,
        ], 200);
    }

    public function destroy(Request $request, int $reviewId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $review = Review::find($reviewId);
        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đánh giá'
            ], 404);
        }

        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa đánh giá này'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa đánh giá'
        ]);
    }
}

