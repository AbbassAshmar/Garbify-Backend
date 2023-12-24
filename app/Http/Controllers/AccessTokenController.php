<?php

namespace App\Http\Controllers;

use App\Enums\TokenAbility;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

use function PHPUnit\Framework\isEmpty;

class AccessTokenController extends Controller
{
    public function isNewestRefreshToken($plainTextRefreshToken){
        $refresh_token = PersonalAccessToken::findToken($plainTextRefreshToken);
        $user = $refresh_token->tokenable;

        $latest_issued_refresh_token = $user->tokens()
        ->where('abilities', 'like', '%' . TokenAbility::ISSUE_ACCESS_TOKEN->value . '%')
        ->latest()
        ->first();

        // there should not be any unused refresh token
        $unused_refresh_tokens = $user->tokens()
        ->where('abilities', 'like', '%' . TokenAbility::ISSUE_ACCESS_TOKEN->value . '%')
        ->where('last_used_at', null)->get();

        $is_not_latest= $latest_issued_refresh_token->token != $refresh_token->token;
        if ( $is_not_latest || $unused_refresh_tokens->toArray()) return false;

        return true;   
    }

    // get a new access token by providing refresh token
    public function createAccessToken(Request $request){
        $user = $request->user();
        $current_refresh_token = $request->bearerToken();

        // if the refresh token is not the newest, the user is malicious
        if (!$this->isNewestRefreshToken($current_refresh_token)){
            $user->tokens()->delete();
            $error = [
                'message' => 'Unauthenticated.',
                'code' => 401,
            ];
            $response_body = HelperController::getFailedResponse($error, null);
            return response($response_body, 401);
        } 

        $token_operations = function () use($user){
            // delete all access tokens of the user 
            $user->tokens()->where("abilities","like","%". TokenAbility::ACCESS_API->value ."%")->delete();

            // create new access and refresh tokens for the user 
            $access_token = UserController::create_access_token($user);
            $refresh_token = UserController::create_refresh_token($user);

            return [$access_token, $refresh_token];
        };
        
        [$access_token,$refresh_token] = HelperController::transaction($token_operations,[$user]);

        // replace the refresh token with the new one
        $cookie = cookie('refresh_token', $refresh_token->plainTextToken,2880);
        $data = ['token' => $access_token->plainTextToken];
        $response_body = HelperController::getSuccessResponse($data,null);

        return response($response_body,201)->withCookie($cookie);
    }
}
