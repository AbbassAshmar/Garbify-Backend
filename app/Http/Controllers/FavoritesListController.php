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
        $user_token =  HelperController::getUserAndToken($request);
        $current_user = $user_token["user"];
    
        $page = $request->input("page");
        $limit = $request->input("limit");
        $sort_by = $request->input("sort_by")?$request->input("sort_by"):"most popular";
        $search = $request->input('q');

        $favorites_lists = FavoritesList::has("favorites")->where("public", true);
        
        if ($sort_by=="most popular") 
            $sort_by = "views_count-5*likes_count+ASC";
            
        if ($search) 
            $favorites_lists = $favorites_lists->where("name","like","%$search%");

        $response_body = HelperController::getCollectionAndCount($favorites_lists,$sort_by, $page, $limit);
        $response_body['data'] = FavoritesListResource::collection_custom($response_body['data'],$current_user);

        return response($response_body, 200);
    }
    
    // get favoritesList of a user by token  (user retreives his own favorites list)
    public function retrieveByUser(Request $request){
        $current_user = $request->user();

        $favorites_list = FavoritesList::where("user_id" , $current_user->id)->first();
        if (!$favorites_list) return response(["message"=>"Favorites list not found."],404);

        $resource = new FavoritesListResource($favorites_list,null,$current_user);
        return response(["data" => $resource], 200);
    }

    // get one FavoritesList by id (user retrieves other user's favorites list)
    public function retrieveById(Request $request ,$id){
        $user_token =  HelperController::getUserAndToken($request);
        $current_user = $user_token["user"];

        $favorites_list = FavoritesList::with("user")->find($id);
        if (!$favorites_list) return response(["message"=>"Favorites list not found."],404);

        $resource = new FavoritesListResource($favorites_list,null,$current_user);
        return response(["data" => $resource], 200);
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

    // view
    public function viewFavoritesList(Request $request ,$id){
        $favorites_list = FavoritesList::find($id);
        if (!$favorites_list) return response(['message' =>'favorites list not found'],400);

        $user_token = HelperController::getUserAndToken($request);
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
