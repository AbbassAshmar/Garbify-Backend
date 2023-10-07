<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\FavoritesList;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Laravel\Sanctum\PersonalAccessToken;

class FavoritesListController extends Controller
{
    const ANONYMOUS_USER_ID = 1;
    
    // creation is done in FavoriteController createFavorite method

    // get all public FavoritesLists of all users
    public function listFavoritesList(Request $request){

    }
    

    // get one FavoriteList by id
    public function retrieveFavoritesList(Request $request ,$id){

    }

    // like and like remove
    public function likeFavoritesList(Request $request ,$id){
        $user = $request->user();
        $favorites_list = FavoritesList::find($id);

        if (!$favorites_list) return response(['message'=>'favorites list not found.'],400);
        
        $like = $favorites_list->likes()->where("user_id", $user->id)->first();
        if ($like){
            $favorites_list->likes()->detach($user->id);
            $new_count = $favorites_list->likes()->count();
            $favorites_list->update(["likes_count" => $new_count]);
            return response(["likes_count" => $new_count,"action"=>"removed"],200);
        }

        $favorites_list->likes()->attach([$user->id]);
        $new_count = $favorites_list->likes()->count();
        $favorites_list->update(["likes_count" => $new_count]);
        return response(["likes_count" => $new_count,"action"=>"added"],200);
    }

    public function getUserAndToken($request){

        $auth_header = $request->header("Authorization");
        if (!$auth_header){
            return ['token'=>null,'user'=>User::find($this::ANONYMOUS_USER_ID)];
        }

        $plain_text_token = explode(" ",$auth_header); //retrieve plainTextToken
        if (count($plain_text_token) <2) {
            return ['token'=>null,'user'=>User::find($this::ANONYMOUS_USER_ID)];
        }

        $token = PersonalAccessToken::findToken($plain_text_token[1]); //retrieve token
        if ($token->tokenable && !(UserController::check_token_expiry($token))){
            return ['token' => $token , 'user' =>$token->tokenable];
        }


        return ['token'=>null,'user'=>User::find($this::ANONYMOUS_USER_ID)];
    }

    // view
    public function viewFavoritesList(Request $request ,$id){
        $favorites_list = FavoritesList::find($id);
        if (!$favorites_list) return response(['message' =>'favorites list not found'],400);

        $user_token= $this->getUserAndToken($request);
        $user = $user_token['user'];
        $token = $user_token['token'];

        if ($token && ($token->can("super-admin") || $token->can("admin"))){
            return response(["views_count"=>$favorites_list->views_count,"action"=>"viewed"],200);
        }

        $favorites_list->views()->attach($user);
        $new_count = $favorites_list->views()->count();
        $favorites_list->update(['views_count' => $new_count]);

        return response(['views_count'=>$new_count,'action'=>'viewed'],200);
    }
}
