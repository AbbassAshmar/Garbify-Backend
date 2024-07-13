<?php


namespace App\Services\AccessToken;

use App\Enums\TokenAbility;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class AccessTokenService {
    public function createAccessToken($user){
        return $user->createToken('access-token',[TokenAbility::ACCESS_API->value],Carbon::now()->addMinutes(20));
    }

    public function isTokenExpired($token){
        return $token->expires_at < Carbon::now(); 
    }

    public static function getUserAndToken($request){
        $return_anonymous = ['token'=>null,'user'=>User::role('anonymous')->first()];
        $auth_header = $request->header("Authorization");

        if (!$auth_header){
            return $return_anonymous;
        }

        $plain_text_token = explode(" ",$auth_header); //retrieve plainTextToken
        if (count($plain_text_token) <2) {
            return $return_anonymous;
        }

        $token = PersonalAccessToken::findToken($plain_text_token[1]); //retrieve token
        if (!$token){
            return $return_anonymous;
        }
        
        if ($token->tokenable && !(self::isTokenExpired($token))){
            return ['token' => $token , 'user' =>$token->tokenable];
        }

        return $return_anonymous;
    }
}