<?php
namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // API: Lấy tất cả dịch vụ
    public function index()
    {
        $services = Service::all();
        return response()->json($services);
    }
}
