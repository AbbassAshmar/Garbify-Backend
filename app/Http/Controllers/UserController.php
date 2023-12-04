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

use function PHPUnit\Framework\isEmpty;

class UserController extends Controller
{
    //helper
    public function createUser($request){
        $valid =$request->validate([
            'name' =>['bail','required', 'string', 'max:256'],
            'email'=>['bail','unique:users,email', 'required', 'email'],
            'password' =>['bail','required' ,'string' ,'regex:/\d/','regex:/.*[A-Z].*/','min:8','same:confirm_password'],
            'confirm_password'=> ['bail','required','same:password'],
        ],[
            'password.min' => 'Password should be at least 8 characters.',
            'password.same' => '',
            'confirm_password.same' => "Passwords do not match.", 
        ]);
        try {
            $new_user = User::create([
                'email' => $valid['email'] , 
                'name'=>$valid['name'],
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
        $user = $request->user();
        $validated_data = $request->validate([
            'email'=>['bail','unique:users,email', 'email'],
            'name'=>['bail', 'max:256','string'],
            'profile_picture'=>['bail','max:5000', 'image','mimes:jpg,jpeg,png'],
            'old_password'=> [
                'bail',
                'required_with:password,confirm_password',
                'string' ,
                'current_password',
            ],
            'password'=> [
                'required_with:old_password,confirm_password',
                'bail',
                'string',
                'regex:/\d/',
                'regex:/.*[A-Z].*/',
                'min:8',
                'same:confirm_password'
            ],
            'confirm_password'=>[
                'required_with:old_password,password',
                'bail',
                'same:password'
            ],
            'id'=>['prohibited'],
            'created_at'=>['prohibited'],
            'updated_at'=>['prohibited'],
        ],[
            'password.min' => 'Password should be at least 8 characters.',
            'created_at.prohibited'=>"You do not have the required authorization to update this field.", 
            'updated_at.prohibited'=>"You do not have the required authorization to update this field.", 
            'id.prohibited'=>"You do not have the required authorization to update this field.", 
            'confirm_password.same'=>"Passwords do not match.",
            "password.required_with" => 'A new password is required to change password.',
            "old_password.required_with" => 'Old password is required to change password.',
            "confirm_password.required_with" => 'confirm password is required to change password.',
            'password.same'=>''
        ]);

        if (empty($validated_data)){
            return response(HelperController::getSuccessResponse(null, null),200);
        }

        // remove old password and confirm password from the update array
        if(isset($validated_data['password'])){
            if(!Hash::check($validated_data['old_password'],$user->password)){
                throw ValidationException::withMessages([
                    'old password' => 'Old password is incorrect.'
                ]);
            }
            unset($validated_data['old_password']);
            unset($validated_data['confirm_password']);
        }

        // store profile picture 
        if(isset($validated_data['profile_picture'])){
            $name = $validated_data['profile_picture']->hashName();
            $path= $validated_data['profile_picture']->storeAs('/public/usersProfilePictures',$name);
            $validated_data['profile_picture'] = basename($path);
        }

        $update = $user->update($validated_data);
        
        if (isset($validated_data['password'])){
            $validated_data['password']= $user->password;
        }

        if(isset($validated_data['profile_picture'])){
            $symlink_dir = '/storage/usersProfilePictures/'.$validated_data['profile_picture'];
            $validated_data['profile_picture'] = asset($symlink_dir);        
        }

        if ($update){
            return HelperController::retrieveResource($validated_data,"user");
        } 

        $error = ['message'=>'Update failed.', 'code'=>400];
        $response_body = HelperController::getFailedResponse($error,null);
        return response($response_body,400);
    }
}
