<?php

namespace App\Http\Controllers\API;

use App\Actions\Fortify\PasswordValidationRules;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
// use Illuminate\Http\Client\Request;

class UserController extends Controller
{
    use PasswordValidationRules;
    public function login(Request $request)
    {
        // $user = User::where('email', $request->email)->first();
        // return $user;
        // return 'rifan';
        try {
            $validator = Validator::make($request->all(), [
                'email'=>'email|required',
                'password'=>'required'
            ]);

            if($validator->fails())
            {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized validator fail'
                ], 'Authentication Faileds', 500);
            }
            // validasi Input 
            // $request->validate([
            //     'email'=>'email|required',
            //     'password'=>'required'
            // ]);

            // //cek credentials
            $credentials = Request(['email', 'password']);
            if(!Auth::attempt($credentials))
            {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Faileds', 500);
            }

            // Jika hash tidak sesuai maka beri error 
            $user = User::where('email', $request->email)->first();
            if(!Hash::check($request->password, $user->password, []))
            {
                throw new \Exception('Invalid Credentials');
            }

            //jika berhasil maka loginkan
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user'=> $user
            ],'Authenticated');
        }
        catch(Exception $error){
            return ResponseFormatter::error([
                'message' => 'Something went wrong catch',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function register (Request $request)
    {
        try{
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' =>  $this -> passwordRules()
            ]);

            User::create([
                    'name'=> $request->name ,    
                    'email'=> $request->email ,
                    'address'=> $request->address  ,
                    'houseNumber'=> $request->houseNumber  ,
                    'phoneNumber'=> $request->phoneNumber ,   
                    'city'=> $request->city ,
                    'password'=> Hash::make($request->password) 
            ]);

            $user = User::where('email', $request->email)-> first();
            // $user = DB::table('users')->where('email', $request->email)-> get();
            $tokenResult = $user -> createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user'=> $user
            ]);
        }
        catch(Exception $error)
        {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }


    }
    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data profile berhasil diambil');
    }

    public function logout(Request $request)
    {
        $tokenRevoked = $request->user()->currentAccessToken()->delete();

        return ResponseFormatter::success($tokenRevoked, 'Token has been revoked');
    }

    public function updateProfile(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $user->update($data);
        
        return ResponseFormatter::success($user, 'Profile has been updated');
    }

    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'=>'image|required|max:2048'
        ]);

        if($validator->fails())
        {
            return ResponseFormatter::error(['error'=>$validator->errors()],'Upload photo fail', 401);
        }
        if($request->file('file'))
        {
            $file = $request->file();
            $file->store('assets/user', 'public');

            $user = Auth::user();
            $user-> profile_photo_path = $file;
            $user->update();
            
            return ResponseFormatter::success([$file], 'file successfully updated');
        }
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(UserRequest $request)
    {
        $data = $request->all();
        $data['photo_profile_path'] = $request->file('photo_profile_path')
                                     ->store('assets/users', 'public');
        User::create($data);
        return redirect() -> route('users.index');
    }

}
