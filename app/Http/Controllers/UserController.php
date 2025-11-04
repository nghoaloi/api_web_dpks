<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
   public function index()
    {
        $users = User::all(); // lấy tất cả bản ghi trong bảng users
        return response()->json($users, 200);
    }
}
