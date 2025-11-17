<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        return response()->json([
            'success' => true,
            'data' => $users
        ], 200);
    }
    // API toggle status user
    public function toggleStatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        }

        // Chuyển trạng thái
        $user->status = $user->status === 'active' ? 'lock' : 'active';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'status' => $user->status
        ]);
    }
}
