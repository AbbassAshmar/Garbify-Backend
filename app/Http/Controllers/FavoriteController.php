<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\FavoritesList;
use Illuminate\Http\Request;
use App\Models\Product;
use Symfony\Component\Console\Input\Input;

class FavoriteController extends Controller
{
    public function createFavorite(Request $request){
        $user = $request->user();
        $product =Product::find($request->input("product_id"));
        $favorites_list = $user->favoritesList;

        // check product existance
        HelperController::checkIfNotFound($product, "Product");
        

        // delete favorite if exists ,else create it 
        $favorite=$favorites_list->favorites()->where("product_id", $product->id)->first();
        if ($favorite){
            $favorite->delete();
            $data = ['action'=>'deleted'];
            return response(HelperController::getSuccessResponse($data,null) , 200);
        }
        
        $data = [
            'product_id'=>$product->id,
            'favorites_list_id'=>$favorites_list->id
        ];
        $favorite = Favorite::create($data);

        $data = ['favorite' => $favorites_list];
        return response(HelperController::getSuccessResponse($data,null), 201);
    }

    // returns all favorites of a favorites list (user get other user's favorites)
    public function listByFavoritesList(Request $request, $id){
        $pageLimit = ['page'=>$request->input("page"),"limit"=>$request->input("limit")];
        $sort_by = $request->input("sort+by");
        $search = $request->input('q');

        $favorites_list = FavoritesList::find($id);
        HelperController::checkIfNotFound($favorites_list,"Favorites list");

        $favorites = $favorites_list->favorites()->with(['product']);

        //search by products name
        if ($search){
            $favorites = $favorites->whereHas("product" , function ($query) use(&$search){
                $query->where("name" , "like" ,"%$search%");
            });
        }

        $response = HelperController::getCollectionAndCount($favorites,$sort_by,$pageLimit,null,"favorites");
        return response($response, 200);
    }

    //returns all favorites of a logged in user by token (used for displaying users' own favorites)
    public function listByUser(Request $request){
        $pageLimit = ['page'=>$request->input("page"),"limit"=>$request->input("limit")];
        $sort_by = $request->input("sort_by");
        $search = $request->input("q");
        $user = $request->user();

        $favorites_list = FavoritesList::where("user_id", $user->id)->first();
        $favorites = Favorite::select("favorites.*")
        ->join("products", "favorites.product_id","=","products.id")
        ->where("favorites_list_id", $favorites_list->id);

        //search by products name
        if ($search){
            $favorites = $favorites->whereHas("product" , function ($query) use(&$search){
                $query->where("name" , "like" ,"%$search%");
            });
        }

        $response = HelperController::getCollectionAndCount($favorites,$sort_by,$pageLimit,null,'favorites');
        return response($response, 200);
    }
}

