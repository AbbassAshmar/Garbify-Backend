<?php

namespace App\Http\Controllers;

use App\Http\Resources\FavoritesListResource;
use App\Models\Favorite;
use App\Models\FavoritesList;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Exceptions\UnauthorizedException;

use function PHPUnit\Framework\isEmpty;

class FavoritesListController extends Controller
{
    
    // creation is done in FavoriteController createFavorite method

    // get all public FavoritesLists of all users
    public function listFavoritesList(Request $request){
        $user_token =  HelperController::getUserAndToken($request);
        $current_user = $user_token["user"];
        $pageLimit= ['page'=>$request->input("page"),'limit'=>$request->input("limit")];
        $sort_by = $request->input("sort_by")?$request->input("sort_by"):"most popular";
        $search = $request->input('q');

        $favorites_lists = FavoritesList::has("favorites")->where("public", true);
        
        if ($sort_by=="most popular") 
            $sort_by = "views_count-5*likes_count+ASC";
            
        if ($search) 
            $favorites_lists = $favorites_lists->where("name","like","%$search%");

        $response_body = HelperController::getCollectionAndCount(
            $favorites_lists,
            $sort_by,$pageLimit,
            null,
            'favorites lists'
        );
        $response_body['data']['favorites lists'] = FavoritesListResource::collection_with_user(
            $response_body['data']['favorites lists'],
            $current_user
        );

        return response($response_body, 200);
    }

    // get favoritesList of a user by token  (user retreives his own favorites list)
    public function retrieveByUser(Request $request){
        $current_user = $request->user();

        $favorites_list = FavoritesList::where("user_id" , $current_user->id)->first();
        $isNull = HelperController::checkIfNotFound($favorites_list,"Favorites list");
        if ($isNull) return $isNull;

        $resource = new FavoritesListResource($favorites_list,null,$current_user);
        return HelperController::retrieveResource($resource,'favorites list');
    }

    // get one FavoritesList by id (user retrieves other user's favorites list)
    public function retrieveById(Request $request ,$id){
        $user_token =  HelperController::getUserAndToken($request);
        $current_user = $user_token["user"];

        $favorites_list = FavoritesList::with("user")->find($id);
        $isNull = HelperController::checkIfNotFound($favorites_list,"Favorites list");
        if ($isNull) return $isNull;

        $resource = new FavoritesListResource($favorites_list,null,$current_user);
        return HelperController::retrieveResource($resource,'favorites list');
    }

    // like and unlike 
    public function likeFavoritesList(Request $request ,$id){
        $user = $request->user();

        $favorites_list = FavoritesList::find($id);
        $isNull = HelperController::checkIfNotFound($favorites_list,"Favorites list");
        if ($isNull) return $isNull;

        $like = $favorites_list->likes()->where("user_id", $user->id)->first();
        if ($like){
            $favorites_list->likes()->detach($user->id);
            $new_count = $favorites_list->likes()->count();
            $favorites_list->update(["likes_count" => $new_count]);

            $data = ["action" =>"unliked"];
            $metadata = ['likes_count' => $new_count];
            return response(HelperController::getSuccessResponse($data,$metadata),200);
        }

        $favorites_list->likes()->attach([$user->id]);
        $new_count = $favorites_list->likes()->count();
        $favorites_list->update(["likes_count" => $new_count]);

        $data = ["action" =>"liked"];
        $metadata = ['likes_count' => $new_count];
        return response(HelperController::getSuccessResponse($data,$metadata),200);
    }

    // view
    public function viewFavoritesList(Request $request ,$id){
        $favorites_list = FavoritesList::find($id);
        $isNull = HelperController::checkIfNotFound($favorites_list,"Favorites list");
        if ($isNull) return $isNull;

        $user_token = HelperController::getUserAndToken($request);
        $user = $user_token['user'];
        $body  = ["action"=>"viewed"];

        // if super-admin or admin view , don't increment number of views
        if ($user && $user->hasRole(['admin','super admin'])){
            $metadata = ["views_count"=>$favorites_list->views_count];
            $response_body = HelperController::getSuccessResponse($body,$metadata);
            return response($response_body,200);
        }

        // if unauthenticated, $user is Anonymous_user
        
        $favorites_list->views()->attach($user);
        $new_count = $favorites_list->views()->count();
        $favorites_list->update(['views_count' => $new_count]);

        $metadata = ["views_count"=>$new_count];
        $response_body = HelperController::getSuccessResponse($body,$metadata);

        return response($response_body,200);
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
        $isNull = HelperController::checkIfNotFound($favorites_list,"Favorites list");
        if ($isNull) return $isNull;

        // check if user trying to update is the owner or an admin
        $owner = $favorites_list->user;
        $user = $request->user();
        
        if ( $owner->id != $user->id && !$user->hasPermissionTo('update_favorites_list') ){
            throw new UnauthorizedException(403,'You do not have the required authorization.');
        }

        if (empty($validated_data)){
            return response(HelperController::getSuccessResponse(null, null),200);
        }
        
        // check if a requested field to be updated is not updatable
        $fields_to_update = array_keys($validated_data);
        $updatable_fields = $favorites_list->getUpdatable();
        if (array_diff($fields_to_update, $updatable_fields) !== []){
            $error = ['message'=>"You do not have the required authorization.",'code'=>403];
            $response_body = HelperController::getFailedResponse($error,null);
            return response($response_body,403);
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
            return HelperController::retrieveResource($validated_data,"favorites list");
        } 

        $error = ['message'=>'Update failed.', 'code'=>400];
        $response_body = HelperController::getFailedResponse($error,null);
        return response($response_body,400);
    }
}
