<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\FavoritesList;
use Illuminate\Http\Request;
use App\Models\Product;

class FavoriteController extends Controller
{
    public function createFavorite(Request $request){
        $user = $request->user();
        $product =Product::find($request->input("product_id"));
        
        // check product existance
        if (!$product)
        return response(['message' =>'product not found.'], 400);

        // create Favorites List for the user if not created
        $favorite_list = FavoritesList::where("user_id", $user->id)->first();
        if (!$favorite_list)
        $favorite_list = FavoritesList::create([
            'user_id' =>$user->id,
            'name' =>$user->name ."'s Favorites",
        ]);

        // delete favorite if exists ,else create it 
        $favorite_instance=$favorite_list->favorites()->where("product_id", $product->id)->first();
        if ($favorite_instance){
            $favorite_instance->delete();
            return response(['action' => 'deleted'] , 200);
        }
        $data = [
            'product_id'=>$product->id,
            'favorites_list_id'=>$favorite_list->id
        ];
        $favorite_instance = Favorite::create($data);
        $response = [
            'action' => 'created',
            'favorite' => $favorite_instance
        ];
        return response($response, 201);
    }

    // returns all favorites of a favorites list (used for displaying other users' favorites)
    public function listByFavoritesList(Request $request, $id){
        $page = $request->input("page");
        $limit = $request->input("limit");
        $sort_by = $request->input("sort+by");
        $search = $request->input('q');

        $favorites_list = FavoritesList::find($id);
        if (!$favorites_list) return response(["message"=>"Favorites list does not exist."], 400);
        
        $favorites = Favorite::with(["product"])->where("favorites_list_id" , $id);

        //search by products name
        if ($search){
            $favorites = $favorites->whereHas("product" , function ($query) use(&$search){
                $query->where("name" , "like" ,"%$search%");
            });
        }

        $total_count = $favorites->count();
        $sorted_favorites = ProductController::sortCollection($favorites, $sort_by);
        $sorted_limited_favorites = ProductController::filterNumber($sorted_favorites, $page, $limit);
        $result= $sorted_limited_favorites->get();
        $favorites_count_after_limit = $result->count();

        $response = [
            "favorites"=> $result,
            "count"=> $favorites_count_after_limit,
            "total_count"=> $total_count
        ];

        return response($response, 200);
    }

    //returns all favorites of a logged in user by token (used for displaying users' own favorites)
    public function listByUser(Request $request){

    }

    //returns favorites 
    public function retrievetById(Request $request){

    }
}

