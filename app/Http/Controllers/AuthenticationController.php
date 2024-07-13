<?php

namespace App\Http\Controllers;

use App\Helpers\GetResponseHelper;
use App\Http\Requests\CreateUserRequest;
use App\Models\User;
use App\Services\AccessToken\AccessTokenService;
use App\Services\FavoritesList\FavoritesListService;
use App\Services\RefreshToken\RefreshTokenService;
use App\Services\User\ShoppingCartService;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use App\Enums\TokenAbility;
use App\Helpers\TransactionHelper;
use Illuminate\Auth\AuthenticationException;


class AuthenticationController extends Controller
{

    private $userService;
    private $favoritesListService;
    private $shoppingCartService;
    private $refreshTokenService;
    private $accessTokenService;

    function __construct(UserService $userService, FavoritesListService $favoritesListService, ShoppingCartService $shoppingCartService, RefreshTokenService $refreshTokenService, AccessTokenService $accessTokenService){
        $this->userService = $userService;
        $this->favoritesListService = $favoritesListService;
        $this->shoppingCartService = $shoppingCartService;
        $this->refreshTokenService = $refreshTokenService;
        $this->accessTokenService = $accessTokenService;
    }


    public function register(CreateUserRequest $request){
        $data = $request->validated();
        ['user'=>$user, 'error' => $error] = $this->userService->createUser($data,"client");

        if ($user == null && $error){
            $error = ['message'=>$error, 'code'=>400];
            $data = ['user'=> null, 'action' => "not created"];
            $response_body = GetResponseHelper::getFailedResponse($error,null, $data);
            return response($response_body,400);
        }

        $this->favoritesListService->createFavoritesListForUser($user);
        $this->shoppingCartService->createShoppingCartForUser($user);

        $refresh_token = $this->refreshTokenService->createRefreshToken($user);
        $access_token = $this->accessTokenService->createAccessToken($user);

        $data=[
            'user'=>$user,
            'token'=>$access_token->plainTextToken,
        ];

        $response_body = GetResponseHelper::getSuccessResponse($data,null);
        $cookie = cookie('refresh_token',$refresh_token->plainTextToken,2880);

        return response($response_body,201)->withCookie($cookie);
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

        // delete all refresh and access tokens of the user
        $user->tokens()->delete();

        $refresh_token = $this->refreshTokenService->createRefreshToken($user);
        $access_token = $this->accessTokenService->createAccessToken($user);

        $data=[
            'user'=>$user,
            'token'=>$access_token->plainTextToken,
        ];

        $response_body = GetResponseHelper::getSuccessResponse($data,null);
        $cookie = cookie('refresh_token',$refresh_token->plainTextToken,2880);

        return response($response_body,201)->withCookie($cookie);
    }

    public function logout(Request $request){
        $user = $request->user();
        try{
            //delete all user's tokens 
            $user->tokens()->delete();
            $data = ['action'=>'deleted'];
            return response(GetResponseHelper::getSuccessResponse($data,null),200);
        }catch(Exception $e){
            $error = [
                'message'=>"An unexpected error occurred while processing your request. 
                 Please try again later.",
                'code'=>500
            ];
            return response(GetResponseHelper::getFailedResponse($error,null),500);
        }
    }

    // get a new access token by providing refresh token
    public function refreshAccessToken(Request $request){
        $user = $request->user();
        $currentRefreshToken = $request->bearerToken();


        // if the user suddenly changes countries, the user is malicious
        if ($this->refreshTokenService->isFromDifferentCountry($request->getClientIp())){
            $user->tokens()->delete();
            throw new AuthenticationException();
        }

        // if the refresh token is not the newest, the user is malicious
        if (!$this->refreshTokenService->isNewestRefreshToken($currentRefreshToken)){
            $user->tokens()->delete();
            throw new AuthenticationException();
        } 

        $token_operations = function () use($user){
            // delete all access tokens of the user 
            $user->tokens()->where("abilities","like","%". TokenAbility::ACCESS_API->value ."%")->delete();

            // create new access and refresh tokens for the user 
            $access_token = $this->accessTokenService->createAccessToken($user);
            $refresh_token = $this->refreshTokenService->createRefreshToken($user);

            return [$access_token, $refresh_token];
        };
        
        [$access_token,$refresh_token] = TransactionHelper::makeTransaction($token_operations,[$user]);

        // replace the refresh token with the new one
        $cookie = cookie('refresh_token', $refresh_token->plainTextToken,2880);
        $data = ['token' => $access_token->plainTextToken];
        $response_body = GetResponseHelper::getSuccessResponse($data,null);

        return response($response_body,201)->withCookie($cookie);
    }
}


