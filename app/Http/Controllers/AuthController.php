<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request){
        try{
            $request->validate([
                'email'=>'required|email',
                'password'=>'required'
            ]);

            if(!Auth::attempt($request->only('email','password'))){
                return response()->json([
                    'message'=>'Email hoặc mật khẩu không chính xác',
                ],401);
            }

            $user=User::where('email',$request->email)->first();

            if($user->status==='lock'){
                return response()->json([
                    'message'=>'Tài khoản đã bị khóa',
                ],401);
            }

            $user->tokens()->delete();
            $token=$user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'token'=>$token,
            ],200);
        }
        catch(\Exception $e){
            return response()->json([
                'message'=>'Đăng nhập thất bại',
                'error'=>$e->getMessage()
            ],500);
        }
    }

    public function register(Request $request){
        try{
            $request->validate([
                'email'=>'required|email|unique:users,email',
                'password'=>'required|string|min:6',
                'fullname'=>'required|string|max:120',
                'phone'=>'required|string|min:10|max:14',
                'gender'=>'nullable|integer|in:0,1',
                'address'=>'nullable|string',
                'status'=>'nullable|string|in:active, lock',
            ],

            [
                'email.unique'=>'Email đã tồn tại',
                'phone.min'=>'Số điện thoại phải từ 10 số trở lên',
                'password'=>'Mật khẩu ít nhất 6 ký tự',
            ]
            );

            $user=User::create([
                'email'=>$request->email,
                'password'=>Hash::make($request->password),
                'fullname'=>$request->fullname,
                'phone'=>$request->phone,
                'gender'=>$request->gender,
                'address'=>$request->address,
                'status'=>$request->status??'active',
            ]);


            return response()->json([
                'message'=>'Đăng ký thành công',
                'data'=>$user
            ],200);
        }
        catch(ValidationException $e){
            return response()->json([
                'message'=>'Lỗi dữ liệu đăng ký',
                'errors'=>$e->errors()
            ],422);
        }
        catch(\Exception $e){
            return response()->json([
                'message'=>'Đăng ký thất bại',
                'error'=>$e->getMessage()
            ],500);
        }
    }

    public function logout(Request $request){
        try{
            $request->user()->tokens()->delete();

            return response()->json([
                'message'=>'Đăng xuất thành công'
            ],200);
        }
        catch(\Exception $e){
            return response()->json([
                'message'=>'Đăng xuất thất bại',
                'error'=>$e->getMessage()
            ],500);
        }
    }

    public function profile(Request $request){
        try{
            $user=$request->user();
            return response()->json([
                'data'=>$user
            ],200);
        }
        catch(\Exception $e){
            return response()->json([
                'message'=>'Lỗi lấy thông tin tài khoản',
                'error'=>$e->getMessage()
            ],500);
        }
    }

    public function updateAvatar(Request $request){
        try{
            $request->validate([
                'avatar'=>'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);

            $user = $request->user();
            $path = $request->file('avatar')->store('avatars', 'public');
            $publicUrl = Storage::url($path);
            $user->update(['avatar' => $publicUrl]);

            return response()->json([
                'message'=>'Cập nhật ảnh thành công',
                'data'=>$user->fresh()
            ],200);
        }
        catch(ValidationException $e){
            return response()->json([
                'message'=>'Lỗi dữ liệu ảnh',
                'errors'=>$e->errors()
            ],422);
        }
        catch(\Exception $e){
            return response()->json([
                'message'=>'Cập nhật ảnh thất bại',
                'error'=>$e->getMessage()
            ],500);
        }
    }

    public function updateProfile(Request $request){
        try{
            $request->validate([
                'fullname'=>'required|string|max:120',
                'phone'=>'nullable|string|min:10|max:14',
                'gender'=>'nullable|integer|in:0,1',
                'address'=>'nullable|string|max:255',
                'avatar'=>'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);

            $user=$request->user();
            $payload = $request->only(['fullname','phone','gender','address']);

            if ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $publicUrl = Storage::url($path);
                $payload['avatar'] = $publicUrl;
            }

            $user->update($payload);

            return response()->json([
                'message'=>'Cập nhật thông tin thành công',
                'data'=>$user
            ],200);
        }
        catch(ValidationException $e){
            return response()->json([
                'message'=>'Lỗi dữ liệu cập nhật',
                'errors'=>$e->errors()
            ],422);
        }
        catch(\Exception $e){
            return response()->json([
                'message'=>'Cập nhật thông tin thất bại',
                'error'=>$e->getMessage()
            ],500);
        }
    }
    
}
