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
}
