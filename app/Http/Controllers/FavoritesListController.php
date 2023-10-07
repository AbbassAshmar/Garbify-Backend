<?php

namespace App\Http\Controllers;

use App\Models\FavoritesList;
use Illuminate\Http\Request;

class FavoritesListController extends Controller
{
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
        if (!$favorites_list)
        return response(['message'=>'favorites list not found.'],400);
        $like = $favorites_list->likes()->where("user_id", $user->id)->first();
        if ($like){
            $favorites_list->likes()->detach($user->id);
            $new_count = $favorites_list->likes()->count();
            $favorites_list->update(["likes_count" => $new_count]);
            return response(["likes_count" => $new_count,"action"=>"removed"],200);
        }
        $favorites_list->users()->attach([$user->id]);


    }

    // view
    public function viewFavoritesList(Request $request ,$id){

    }
}
