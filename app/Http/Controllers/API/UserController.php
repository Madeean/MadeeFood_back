<?php

namespace App\Http\Controllers\API;

use App\Actions\Fortify\PasswordValidationRules;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use PasswordValidationRules;

    public function login(Request $request){
        try{
            $request->validate([
                'email'=>'email|required',
                'password'=>'required',
            ]);

            $credentials = request([['email','password']]);
            if(!Auth::attempt($credentials)){
                return ResponseFormatter::error([
                    'message'=>'Unauthorized',
                    
                ],'Authentication Failed',500);
            }

            $user = User::where('email',$request->email)->first();
            if(!Hash::check($request->password, $user->password, [])){
                throw new \Exception('Invalid Credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token'=>$tokenResult,
                'token_type'=> 'Bearer',
                'user'=>$user
            ],'Authenticated');

        }catch(Exception $e){
            return ResponseFormatter::error([
                'message'=>'Something went wrong',
                'error'=>$e,
            ],'Authentication Failed',500);
        }
    }

    public function register(Request $request){
        try {
            $request->validate([
                'name'=>'required|string|max:256',
                'email'=>'required|email|string|unique:users',
                'password'=>$this->passwordRules(),
            ]);

            User::crete([
                'name'=>$request->name,
                'email'=>$request->email,
                'address'=>$request->address,
                'houseNumber'=>$request->houseNumber,
                'phoneNumber'=>$request->phoneNumber,
                'city'=>$request->city,
                'password'=>Hash::make($request->password),
            ]);

            $user = User::where('email',$request->email)->first();
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token'=>$tokenResult,
                'token_type'=>'Bearer',
                'user'=>$user
            ]);
        } catch (Exception $e) {
            return ResponseFormatter::error([
                'message'=>'Something went wrong',
                'error'=>$e
            ],'Authentication Failed',500);
        }
    }

    public function logout(Request $request){
        $token = $request->user()->currentAccessToken()->delete();
        return ResponseFormatter::success($token,'Token Removed');
    }

    public function fetch(Request $request){
        return ResponseFormatter::success($request->user(),'Data Profile User Berhasil Diambil');
    }

    public function updateProfile(Request $request){
        $data = $request->all();

        $user = Auth::user();
        $user->update($data);

        return ResponseFormatter::success($user, 'Profile Updated');
    }

    public function updatePhoto(Request $request){
        $validator = Validator::make($request->all(),[
            'file'=>'required|image|max:2048'
        ]);
        if($validator->failed()){
            return ResponseFormatter::error([
                'error'=>$validator->errors(),
            ],'Update Foto Fails',401);
        }

        if($request->file('file')){
            $file = $request->file->store('assets/user', 'public');

            $user = Auth::user();
            $user->profile_photo_url=$file;
            $user->update();

            return ResponseFormatter::success([$file],'File succesfully uploadid');
        }
    }

}