<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOption\None;
use App\Models\User;
use DateTime;
use Illuminate\Support\Facades\Hash;
use DateTimeInterface;
use Error;
use Exception;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\FavoritesList;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Validation\ValidationData;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    //helper
    public function createUser($request){
        $valid =$request->validate([
            'username' =>['bail','required', 'string', 'max:256'],
            'email'=>['bail','unique:users,email', 'required', 'email'],
            'password' =>['required' ,'string' ,'regex:/\d/','min:8','same:confirm_password'],
            'confirm_password'=> ['required','same:password'],
        ],['confirm_password.same' => "Passwords do not match.", 'password.same' => '']);
        try {
            $new_user = User::create([
                'email' => $valid['email'] , 
                'name'=>$valid['username'],
                'password'=>$valid['password'],
                'profile_picture'=>null
            ]);
        }catch(Exception $e){
            return null;
        }

        $this->createFavoritesList($new_user);
        return $new_user;
    }
    
    //helper
    public function createFavoritesList($user){
        $favorites_list = FavoritesList::where("user_id", $user->id)->first();
        if (!$favorites_list){
            $favorites_list = FavoritesList::create([
                'user_id' =>$user->id,
                'name' =>$user->name ."'s Favorites",
            ]);
        }
        return $favorites_list;
    }

    //helper
    protected function create_token($user,array $abilities=['client']){
        return $user->createToken("user_token",$abilities,Carbon::now()->addDays(1));
    }
    
    //helper
    public static function check_token_expiry($token){
        if ($token->expires_at < Carbon::now()){
            // token expired
            return true;
        }
        return false; 
    }

    public function register(Request $request){
        $new_user = $this->createUser($request);
        if (!$new_user){
            $error = [
                'message'=>"An unexpected error occurred while processing your request. 
                 Please try again later.",
                'code'=>400
            ];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body,400);
        }

        $token = $this->create_token($new_user,[]);
        $data=[
            'user'=>$new_user,
            'token'=>$token->plainTextToken,
        ];
        $response_body = HelperController::getSuccessResponse($data,null);
        return response($response_body,201);
    }
    
    public function login(Request $request){
        $valid = $request->validate([
            'email' => ['bail','required', 'email','exists:users,email'],
            'password'=> ['bail','required' , 'string' ,'regex:/\d/','min:8'],
        ]);

        $email =$valid['email'];
        $password = $valid["password"];
        $user = User::where('email', $email)->first();
    
        if (!Hash::check($password,$user->password)){
            throw ValidationException::withMessages([
                'password' => 'Email and password do not match.',
                'email'=>'' // added in error_fields but not in messages details
            ]);
        }

        $token = $user->tokens()->first();
        if ($token != null) $token->delete();
        $token =$this->create_token($user)->plainTextToken;
        
        $data=[
            'user'=>$user,
            'token'=>$token->plainTextToken,
        ];
        $response_body = HelperController::getSuccessResponse($data,null);
        return response($response_body,201);
    }

    public function logout(Request $request){
        $token_plain_text = $request->bearerToken();
        try{
            $token =PersonalAccessToken::findToken($token_plain_text);
            $token->delete();
            $data = ['action'=>'deleted'];
            return response(HelperController::getSuccessResponse($data,null),200);
        }catch(Exception $e){
            $error = [
                'message'=>"An unexpected error occurred while processing your request. 
                 Please try again later.",
                'code'=>500
            ];
            return response(HelperController::getFailedResponse($error,null),500);
        }
    }

    public function adminRegister(Request $request){
        $new_user = $this->createUser($request);
        $new_user->assignRole('admin');
        $token = $this->create_token($new_user);
        $body = [
            'token'=>$token->plainTextToken,
            'user'=>$new_user
        ];
        $response_body = HelperController::getSuccessResponse($body,null);
        return response($response_body, 201);
    }

    public function updateUser(Request $request){

    }
}
