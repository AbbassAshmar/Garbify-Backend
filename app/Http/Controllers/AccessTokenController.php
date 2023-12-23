<?php

namespace App\Http\Controllers;

use App\Enums\TokenAbility;
use Illuminate\Http\Request;

class AccessTokenController extends Controller
{
    // get a new access token by providing refresh token
    public function createAccessToken(Request $request){
        $user = $request->user();
        
        // delete all access tokens of the user 
        foreach($user->tokens as $token){
            if($token->can(TokenAbility::ACCESS_API->value)) $token->delete(); 
        }

        // create a new access token for the user 
        $access_token = UserController::create_access_token($user);
        $data = ['token' => $access_token->plainTextToken];
        $response_body = HelperController::getSuccessResponse($data,null);

        return response($response_body,201);
    }
}
