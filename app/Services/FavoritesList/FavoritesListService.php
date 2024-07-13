<?php

namespace App\Services\FavoritesList;

use App\Models\FavoritesList;

class FavoritesListService {
    public function createFavoritesListForUser($user){
        if (!$user) return ["favoritesList" => null, 'error' => "No user provided"];

        $favorites_list = FavoritesList::where("user_id", $user->id)->first();
        if (!$favorites_list){
            $favorites_list = FavoritesList::create([
                'user_id' =>$user->id,
                'name' =>$user->name ."'s Favorites",
            ]);
        }

        return $favorites_list;
    }


}

