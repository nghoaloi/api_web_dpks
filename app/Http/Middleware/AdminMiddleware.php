<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
public function handle(Request $request, Closure $next)
{
    $user = $request->user();

    if (!$user) {
        \Log::info('AdminMiddleware: user is null'); 
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    \Log::info('AdminMiddleware: user role = ' . $user->role);

    if ($user->role !== 'admin') {
        return response()->json(['message' => 'Bạn không có quyền truy cập!'], 403);
    }

    return $next($request);
}

}
