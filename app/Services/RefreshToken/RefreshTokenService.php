<?php


namespace App\Services\RefreshToken;

use App\Enums\TokenAbility;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;
use Stevebauman\Location\Facades\Location;

class RefreshTokenService {
    public function createRefreshToken($user){
        return $user->createToken('refresh-token',[TokenAbility::ISSUE_ACCESS_TOKEN->value],Carbon::now()->addMinutes(2880));
    }

    public function isTokenExpired($token){
        return $token->expires_at < Carbon::now(); 
    }

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

    //compare user's location with the previous user's location
    public function isFromDifferentCountry($ip){
        $current_user_location = Location::get($ip);
        if (!$current_user_location) return false;
        
        $current_user_country = $current_user_location->countryName;
        $previous_user_country = session()->get('user_location_country',null);

        // update the previous location to current
        session()->put('user_location_country', $current_user_country);

        if (!$previous_user_country) return false;
        return $previous_user_country != $current_user_country;
    }
}