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

class UserController extends Controller
{
    static function validate_email($email){
        if ($email == null ){
            return ['msg' =>'Please enter your email','error'=>"email"];
        }
        if (preg_match("/.+\@[a-zA-Z]+\.com/", $email) ==0 ){
            return ['msg'=>'invalid email'];
        }
        return null;
    }

    static function validate_username($username){
        if ($username == null){
            return ['msg' =>'Please enter a username','error'=>"username"];
        }
        return null;
    }
    
    static function validate_password($password, $confirm_password){
        if ($password == null ){
            return ['msg' => 'Please enter a password','error'=>"password"];
        }
        if ($confirm_password == null ){
            return ['msg' => 'Please enter the password again to confirm it','error'=>"confirm_password"];
        }
        if (strlen($password) <8){
            return ['msg' => 'Password should be more than 8 characters','error'=>"password"];
        }   
        if (preg_match("/\d/",$password) + preg_match("/[a-zA-Z]/",$password) != 2){
            return ['msg' => 'Password must contain digits and characters','error'=>"password"];
        }
        if ($password != $confirm_password){
            return ['msg' => 'Passwords do not match','error'=>"passwordConfirm"];
        }
        return null;
    }
    
    protected function create_token($user,array $abilities=['client']){
        return $user->createToken("user_token",$abilities,Carbon::now()->addDays(1));
    }
    
    public static function check_token_expiry($token){
        if ($token->expires_at < Carbon::now()){
            // token expired
            return true;
        }
        return false; 
    }


    public function adminRegister(Request $request){
        $new_user = $this->createUser($request);
        $token = $this->create_token($new_user,['admin']);
        $response_json = [
            'token'=>$token->plainTextToken,
            'user'=>$new_user
        ];
        return response($response_json, 201);
    }


    public function createUser($request){
        $valid =$request->validate(
            [
                'username' =>['bail','required', 'string', 'max:256'],
                'email'=>['bail','unique:users,email', 'required', 'email'],
                'password' =>['required' ,'string' ,'regex:/\d/','min:8',],
                'confirm_password'=> ['required','same:password'],
            ]);
        $new_user = User::create(['email' => $valid['email'] , 'name'=>$valid['username'],'password'=>$valid['password']]);
        return $new_user;
    }
    public function register(Request $request){
        $new_user = $this->createUser($request);
        $token = $this->create_token($new_user,['client']);
        $response_json=[
            'user'=>$new_user,
            'token'=>$token->plainTextToken,
        ];

        return response($response_json,201);
    }
    
    public function login(Request $request){
        $valid = $request->validate(
            [
                'email' => ['bail','required', 'email'],
                'password'=> ['bail','required' , 'string' ,'regex:/\d/','min:8'],
            ]
        );
        $email =$valid['email'];
        $password = $valid["password"];
        
        
        $user = User::where('email', $email)->first();
        
        if ($user == null){
            return response(
                [
                    "message"=> "email does not exist.",
                    "errors"=> [
                        "email"=> [
                            "email does not exist."
                        ]
                    ]
                ],422);
        }
    
        if (!Hash::check($password,$user->password)){
            return response(
                [
                    "message"=> "email and password do not match.",
                    "errors"=> [
                        "password"=> [
                            "email and password do not match."
                        ],
                        'email'=>['']
                    ]
                ],422);
        }

        $token = $user->tokens()->first();
        
        if ($token != null){
            $token->delete();
        }
        $token =$this->create_token($user)->plainTextToken;
        $response_json = [
            'user'=>$user,
            'token'=>$token
        ];
        
        return response($response_json,200);
    }


    public function logout(Request $request){
        $token_plain_text = $request->bearerToken();
        try{
            $token =PersonalAccessToken::findToken($token_plain_text);
            $token->delete();
            return response(['deleted' => 'true'],200);
        }catch(Error){
            return response(['deleted' => 'false'],500);
        }
    }
}
