<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Models\RoomType;
use App\Models\UserVoucher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $wasExistingReview = Review::where('user_id', $user->id)
            ->where('type_id', $roomTypeId)
            ->exists();

        $review = Review::updateOrCreate(
            ['user_id' => $user->id, 'type_id' => $roomTypeId],
            [
                'rating' => $request->input('rating'),
                'comment' => $request->input('comment'),
            ]
        );

        $review->load(['user:id,fullname,avatar']);

        $rewarded = false;
        $rewardMessage = null;

        try {
            
            if (!$wasExistingReview) {

                $alreadyHasReviewReward = UserVoucher::where('user_id', $user->id)
                    ->where('source', 'reward_review')
                    ->exists();

                if (!$alreadyHasReviewReward) {
                    $rewardVoucher = Voucher::where('code', 'REVIEW50K')->first();
                    
                    if ($rewardVoucher) {
                        $now = now();
                        $isVoucherValid = $rewardVoucher->status === 'active' 
                            && (!$rewardVoucher->start_date || $rewardVoucher->start_date <= $now)
                            && (!$rewardVoucher->end_date || $rewardVoucher->end_date >= $now);
                        
                        if ($isVoucherValid) {
                            UserVoucher::create([
                                'user_id' => $user->id,
                                'voucher_id' => $rewardVoucher->id,
                                'is_used' => false,
                                'expired_at' => $rewardVoucher->end_date,
                                'source' => 'reward_review',
                            ]);
                            $rewarded = true;
                            $rewardMessage = 'Cảm ơn bạn đã đánh giá! Bạn vừa nhận được một mã giảm giá trong tài khoản.';
                            
                            Log::info('Review reward voucher granted', [
                                'user_id' => $user->id,
                                'room_type_id' => $roomTypeId,
                                'voucher_id' => $rewardVoucher->id,
                            ]);
                        } else {
                            Log::warning('Review reward voucher exists but is not valid', [
                                'user_id' => $user->id,
                                'voucher_id' => $rewardVoucher->id,
                                'status' => $rewardVoucher->status,
                                'start_date' => $rewardVoucher->start_date,
                                'end_date' => $rewardVoucher->end_date,
                            ]);
                        }
                    } else {
                        Log::warning('Review reward voucher REVIEW50K not found', [
                            'user_id' => $user->id,
                            'room_type_id' => $roomTypeId,
                        ]);
                    }
                } else {
                    Log::info('User already has review reward voucher', [
                        'user_id' => $user->id,
                        'room_type_id' => $roomTypeId,
                    ]);
                }
            } else {
                Log::info('Review is update, not new review - no reward', [
                    'user_id' => $user->id,
                    'room_type_id' => $roomTypeId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Reward review voucher failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'room_type_id' => $roomTypeId,
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đánh giá đã được lưu',
            'data' => $review,
            'rewarded' => $rewarded,
            'reward_message' => $rewardMessage,
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

