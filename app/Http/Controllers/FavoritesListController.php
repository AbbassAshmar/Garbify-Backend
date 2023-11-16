<?php

namespace App\Http\Controllers;

use App\Http\Resources\FavoritesListResource;
use App\Models\Favorite;
use App\Models\FavoritesList;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Laravel\Sanctum\PersonalAccessToken;

use function PHPUnit\Framework\isEmpty;

class FavoritesListController extends Controller
{
    
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
        $response_body['data'] = FavoritesListResource::collection_with_user($response_body['data'],$current_user);

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

        // if super-admin or admin view , don't increment number of views
        if ($token && ($token->can("super-admin") || $token->can("admin"))){
            return response(["views_count"=>$favorites_list->views_count,"action"=>"viewed"],200);
        }

        // if unauthenticated, $user is Anonymous_user
        // dd($user);
        $favorites_list->views()->attach($user);
        $new_count = $favorites_list->views()->count();
        $favorites_list->update(['views_count' => $new_count]);

        return response(['user'=>$user->name,'views_count'=>$new_count,'action'=>'viewed'],200);
    }

    // update one or more of the fields 
    public function updateFavoritesList(Request $request, $id){
        
        $validated_data = $request->validate([
            "name" => ["bail", "max:30", "string", "unique:App\Models\FavoritesList,name"],
            "thumbnail" => ['bail' , 'max:5000', 'mimes:jpeg,jpg,png','image'],
            'public' => ['bail', 'boolean'],
            'id' => ['sometimes'],
            'createdAt' => ['sometimes']
        ]);

        $favorites_list = FavoritesList::find($id);
        if (!$favorites_list){
            return response(['message'=>'Favorites list does not exist.'],400);
        }

        // check if user trying to update is the owner or an admin
        $owner = $favorites_list->user;
        $user = $request->user();
        if ($owner->id != $user->id && !($user->tokenCan('super-admin') || $user->tokenCan("admin"))){
            return response(["message"=>"You do not have permission to update this resource."],403);
        }

        if (empty($validated_data)){
            return response([],204);
        }
        
        // check if a requested to update field is not updatable
        $fields_to_update = array_keys($validated_data);
        $updatable_fields = $favorites_list->getUpdatable();
        if (array_diff($fields_to_update, $updatable_fields) !== []){
            return response(['message'=>"You do not have permission to update this field."],403);
        }

        // if image is present store it at storage/app/public/favoritesListsThumbnails
        // to retrieve it use asset(/public/storage/favoritesListsThumbnails, $name_from_db) asset(symlink,$name)
        if (isset($validated_data['thumbnail'])){
            $name = $validated_data['thumbnail']->hashName(); //create unique name for image
            //storeAs could add a number to the end of the name used if duplicates (could change $name)
            $path = $validated_data['thumbnail']->storeAs('public/favoritesListsThumbnails/',$name); 
            $validated_data['thumbnail'] = basename($path); //the unique name is updated in db later
        }

        // update fields 
        $update = $favorites_list->update($validated_data);
        if (isset($validated_data['thumbnail'])){
            $symlink_dir = '/storage/favoritesListsThumbnails/'.$validated_data['thumbnail'];
            $validated_data['thumbnail'] = asset($symlink_dir);
        }

        if ($update){
            $response_body = ['data'=>$validated_data];
            return response($response_body,200);
        } 

        return response(['message'=>'Update failed.'],400);
    }
}
