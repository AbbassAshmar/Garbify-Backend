<?php

namespace App\Http\Controllers;

use App\Http\Resources\FavoritesListResource;
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
        $page = $request->input("page");
        $limit = $request->input("limit");
        $sort_by = $request->input("sort_by")?$request->input("sort_by"):"most popular";
        $search = $request->input('q');

        $favorites_lists = FavoritesList::has("favorites");
        
        if ($sort_by=="most popular") 
            $sort_by = "views_count-5*likes_count+ASC";
            
        if ($search) {
            $search = str_replace("+"," ", $search);
            $favorites_lists = $favorites_lists->where("name","like","%$search%");
        }
        
        $favorites_total_count = $favorites_lists->count();

        $sorted_favorites_lists = ProductController::sortCollection($favorites_lists,$sort_by);
        $limited_sorted_favorites_lists = ProductController::filterNumber($sorted_favorites_lists, $page,$limit);
        $result = $limited_sorted_favorites_lists->with("user")->get();
        $favorites_lists_count_after_limit = $result->count();

        $response_body = [
            "favorites_lists" => $result,
            "count"=>$favorites_lists_count_after_limit,
            "total_count"=>$favorites_total_count
        ];
        
        return response($response_body, 200);
    }
    
    // get favoritesList of a user by token 
    public function retrieveByUser(Request $request){
        $user = $request->user();
        $favorites_list = FavoritesList::where("user_id" , $user->id)->first();
        return response(["favorites_list" => $favorites_list], 200);
    }

    // get one FavoritesList by id
    public function retrieveById(Request $request ,$id){
        $favorites_list = FavoritesList::with("user")->find($id);
        if (!$favorites_list) return response(["message"=>"Favorites list not found."],404);
        return response(['favorites_list'=>$favorites_list],200);
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
