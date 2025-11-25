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
        return response()->json([
            'data' => $services
        ]);
    }

    // API: Lấy chi tiết 1 dịch vụ theo ID
    public function show($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Không tìm thấy dịch vụ'], 404);
        }

        return response()->json([
            'data' => $service
        ]);
    }

    // API: Thêm dịch vụ
    public function store(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'service_name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string'
            ]);
    
            $service = Service::create($validatedData);
    
            return response()->json([
                'message' => 'Thêm dịch vụ thành công!',
                'data' => $service
            ], 200);
        }
        catch(\Exception $e){
            return response()->json([
                'message' => 'Lỗi khi thêm dịch vụ: ' . $e->getMessage()
            ], 500);
        }
        
    }

    // API: Cập nhật dịch vụ
    public function update(Request $request, $id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Không tìm thấy dịch vụ'], 404);
        }

        $validatedData = $request->validate([
            'service_name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        $service->update($validatedData);
    
        return response()->json([
            'message' => 'Cập nhật dịch vụ thành công!',
            'data' => $service
        ]);
    }
    // API: Xóa dịch vụ
    public function destroy($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Không tìm thấy dịch vụ'], 404);
        }
        $service->delete();
        return response()->json([
            'message' => 'Dịch vụ đã được xóa!'
        ]);
    }
}
